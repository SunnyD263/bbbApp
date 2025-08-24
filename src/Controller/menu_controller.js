import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    const form = document.getElementById('post-baagl');
    const attach = (id) => {
      const link = document.getElementById(id);
      if (!link || !form) return;
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const source = link.getAttribute('data-source') || '';
        form.querySelector('input[name="source"]').value = source;
        form.submit();
      });
    };

    attach('baagl-shoptet');
    attach('update-shoptet');
  }
}
