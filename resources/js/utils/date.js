const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function pad(n) {
  return String(n).padStart(2, '0');
}

// e.g. 12-Jul-2026
export function formatDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (isNaN(date)) return '—';

  return `${pad(date.getDate())}-${MONTHS[date.getMonth()]}-${date.getFullYear()}`;
}

// e.g. 12-Jul-2026 10:10 AM
export function formatDateTime(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (isNaN(date)) return '—';

  let hours = date.getHours();
  const minutes = pad(date.getMinutes());
  const period = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12;

  return `${formatDate(date)} ${pad(hours)}:${minutes} ${period}`;
}
