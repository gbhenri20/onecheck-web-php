document.addEventListener('DOMContentLoaded', () => {
  const alerts = document.querySelectorAll('[data-auto-dismiss]');
  alerts.forEach((el) => {
    setTimeout(() => {
      const alert = bootstrap.Alert.getOrCreateInstance(el);
      alert.close();
    }, 5000);
  });
});
