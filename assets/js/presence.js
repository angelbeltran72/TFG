(() => {
  const INTERVAL = 60_000; // 60 segundos

  function ping() {
    fetch('index.php?controller=Presence&action=verEstado', {
      method: 'GET',
      credentials: 'same-origin',
    })
      .then(res => {
        if (res.status === 401) {
          // Sesión expirada — redirigir al login
          window.location.href = 'index.php?controller=Auth&action=iniciarSesion';
        }
      })
      .catch(() => { /* sin conexión, ignorar */ });
  }

  // Ping inmediato al cargar y luego cada minuto
  ping();
  setInterval(ping, INTERVAL);
})();
