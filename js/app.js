function openModal(id) {
  const m = document.getElementById(id), f = m.querySelector('form');
  f.reset(); f.elements['id'].value = ''; if (f.elements['quantity']) f.elements['quantity'].disabled = false; m.classList.add('open');
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function editRow(btn, id) {
  openModal(id);
  const data = JSON.parse(btn.dataset.row), f = document.querySelector('#' + id + ' form');
  for (const k in data) if (f.elements[k] && k !== 'quantity') f.elements[k].value = data[k] ?? '';
  const q = f.elements['quantity']; if (q) { q.value = data.quantity; q.disabled = true; }
}
document.addEventListener('click', e => { if (e.target.classList.contains('modal')) e.target.classList.remove('open'); });
document.querySelectorAll('.alert').forEach(a => setTimeout(() => a.style.display = 'none', 5000));
