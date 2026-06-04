document.addEventListener('DOMContentLoaded', () => {
  const cepInput = document.getElementById('campo-cep');
  const btnCep = document.getElementById('btn-buscar-cep');
  if (!cepInput || !btnCep) return;

  const base = document.body.dataset.baseUrl || '';

  async function buscarCep() {
    const cep = cepInput.value.replace(/\D/g, '');
    if (cep.length !== 8) {
      alert('Informe um CEP válido com 8 dígitos.');
      return;
    }
    btnCep.disabled = true;
    btnCep.textContent = '...';
    try {
      const res = await fetch(`${base}api/geocode/cep.php?cep=${cep}`);
      const data = await res.json();
      if (!data.ok) {
        alert(data.error || 'CEP não encontrado.');
        return;
      }
      const e = data.endereco;
      const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val || '';
      };
      set('campo-logradouro', e.logradouro);
      set('campo-bairro', e.bairro);
      set('campo-cidade', e.cidade);
      set('campo-estado', e.estado);
      const geo = document.getElementById('geo-status');
      if (geo) geo.textContent = 'Endereço preenchido via ViaCEP. Salve para obter GPS.';
    } catch {
      alert('Erro ao consultar CEP.');
    } finally {
      btnCep.disabled = false;
      btnCep.textContent = 'Buscar';
    }
  }

  btnCep.addEventListener('click', buscarCep);
  cepInput.addEventListener('blur', () => {
    if (cepInput.value.replace(/\D/g, '').length === 8) buscarCep();
  });
});
