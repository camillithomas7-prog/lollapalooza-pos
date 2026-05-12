// Web Bluetooth + Web Serial bridge per stampanti termiche ESC/POS.
//
// Espone un singleton globale window.EscPosPrinter con:
//   - connectBluetooth() / connectUSB()  → richiede dispositivo all utente
//   - reconnect()                         → ritenta su un dispositivo già autorizzato
//   - sendBytes(b64Payload)               → invia raw bytes alla stampante
//   - isConnected, status, on('change', fn)
//
// Web Bluetooth: SPP/GATT service. Le stampanti termiche cinesi (Munbyn,
// Goojprt, Xprinter BT) espongono di solito uno dei seguenti service UUID.
// Tentiamo tutti finché non trova quello che funziona.

(function (global) {
  const STATUS = {
    DISCONNECTED: 'disconnected',
    CONNECTING:   'connecting',
    READY:        'ready',
    ERROR:        'error',
  };

  // UUID di servizi BLE noti per stampanti termiche ESC/POS.
  // Si tenta nell ordine; il primo che fornisce una characteristic "write"
  // viene memorizzato.
  const KNOWN_SERVICES = [
    '0000ff00-0000-1000-8000-00805f9b34fb', // Xprinter / Goojprt
    '0000ae30-0000-1000-8000-00805f9b34fb', // Munbyn
    '000018f0-0000-1000-8000-00805f9b34fb', // generico HM-10/Nordic
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // Star micronics
    '49535343-fe7d-4ae5-8fa9-9fafd205e455', // generic SPP-over-BLE
  ];

  const state = {
    status: STATUS.DISCONNECTED,
    method: null,        // 'bluetooth' | 'usb' | 'serial' | null
    device: null,
    gattServer: null,
    characteristic: null,
    serialPort: null,
    writer: null,
    listeners: [],
    lastError: null,
  };

  function setStatus(s, err) {
    state.status = s;
    state.lastError = err || null;
    state.listeners.forEach(fn => { try { fn(state); } catch(e){} });
  }

  function on(event, fn) {
    if (event === 'change' && typeof fn === 'function') state.listeners.push(fn);
  }

  function isConnected() { return state.status === STATUS.READY; }

  function b64ToBytes(b64) {
    const bin = atob(b64);
    const arr = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
    return arr;
  }

  // ============ BLUETOOTH ============

  async function connectBluetooth() {
    if (!navigator.bluetooth) {
      setStatus(STATUS.ERROR, 'Web Bluetooth non supportato (su iPhone/iPad non funziona — usa Android o PC con Chrome).');
      throw new Error(state.lastError);
    }
    setStatus(STATUS.CONNECTING);
    try {
      const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: KNOWN_SERVICES,
      });
      await bindBluetoothDevice(device);
      // Salva l ID del dispositivo per ricollegarsi al refresh
      try { localStorage.setItem('escpos_bt_device_id', device.id || device.name || ''); } catch(e){}
    } catch (e) {
      setStatus(STATUS.DISCONNECTED, e.message || 'connessione annullata');
      throw e;
    }
  }

  async function bindBluetoothDevice(device) {
    state.device = device;
    state.method = 'bluetooth';
    device.addEventListener('gattserverdisconnected', () => {
      state.gattServer = null;
      state.characteristic = null;
      setStatus(STATUS.DISCONNECTED, 'Stampante scollegata');
    });
    const server = await device.gatt.connect();
    state.gattServer = server;

    // Trova il primo service che ha una characteristic writable
    const services = await server.getPrimaryServices();
    let chosen = null;
    for (const svc of services) {
      try {
        const chars = await svc.getCharacteristics();
        for (const c of chars) {
          if (c.properties.write || c.properties.writeWithoutResponse) {
            chosen = c; break;
          }
        }
        if (chosen) break;
      } catch(e){ /* skip */ }
    }
    if (!chosen) {
      throw new Error('Nessuna characteristic scrivibile trovata sulla stampante. La stampante è ESC/POS?');
    }
    state.characteristic = chosen;
    setStatus(STATUS.READY);
  }

  // Prova a ricollegarsi a un dispositivo già autorizzato (no UI prompt)
  async function reconnect() {
    if (!navigator.bluetooth || !navigator.bluetooth.getDevices) return false;
    try {
      const devices = await navigator.bluetooth.getDevices();
      const savedId = (() => { try { return localStorage.getItem('escpos_bt_device_id'); } catch(e){ return null; } })();
      const preferred = devices.find(d => d.id === savedId || d.name === savedId) || devices[0];
      if (!preferred) return false;
      setStatus(STATUS.CONNECTING);
      await bindBluetoothDevice(preferred);
      return true;
    } catch (e) {
      setStatus(STATUS.DISCONNECTED, e.message);
      return false;
    }
  }

  // ============ WEB SERIAL (USB) — fallback per stampanti USB su PC ============

  async function connectUSB() {
    if (!navigator.serial) {
      setStatus(STATUS.ERROR, 'Web Serial non supportato — su Chrome desktop Windows/Linux/Mac.');
      throw new Error(state.lastError);
    }
    setStatus(STATUS.CONNECTING);
    try {
      const port = await navigator.serial.requestPort();
      await port.open({ baudRate: 9600 });
      state.serialPort = port;
      state.writer = port.writable.getWriter();
      state.method = 'serial';
      setStatus(STATUS.READY);
    } catch (e) {
      setStatus(STATUS.DISCONNECTED, e.message);
      throw e;
    }
  }

  // ============ SEND ============

  async function sendBytes(b64) {
    if (!isConnected()) throw new Error('Stampante non connessa');
    const bytes = b64ToBytes(b64);

    if (state.method === 'bluetooth') {
      // BLE characteristic non accetta pacchetti grandi: spezza a chunk di 180 byte.
      const CHUNK = 180;
      for (let i = 0; i < bytes.length; i += CHUNK) {
        const slice = bytes.slice(i, i + CHUNK);
        // writeWithoutResponse è più veloce; cade su writeValue se non c è
        if (state.characteristic.writeValueWithoutResponse) {
          await state.characteristic.writeValueWithoutResponse(slice);
        } else {
          await state.characteristic.writeValue(slice);
        }
        // Piccola pausa per non saturare il buffer della stampante
        await new Promise(r => setTimeout(r, 30));
      }
    } else if (state.method === 'serial') {
      await state.writer.write(bytes);
    } else {
      throw new Error('Metodo di connessione sconosciuto');
    }
  }

  async function disconnect() {
    try {
      if (state.method === 'bluetooth' && state.gattServer && state.gattServer.connected) {
        state.gattServer.disconnect();
      }
      if (state.method === 'serial' && state.serialPort) {
        try { state.writer.releaseLock(); } catch(e){}
        try { await state.serialPort.close(); } catch(e){}
      }
    } catch(e){}
    state.device = null;
    state.gattServer = null;
    state.characteristic = null;
    state.serialPort = null;
    state.writer = null;
    state.method = null;
    try { localStorage.removeItem('escpos_bt_device_id'); } catch(e){}
    setStatus(STATUS.DISCONNECTED);
  }

  global.EscPosPrinter = {
    STATUS,
    connectBluetooth,
    connectUSB,
    reconnect,
    disconnect,
    sendBytes,
    isConnected: () => isConnected(),
    getStatus: () => state.status,
    getMethod: () => state.method,
    getDeviceName: () => state.device && state.device.name,
    getLastError: () => state.lastError,
    on,
  };
})(window);
