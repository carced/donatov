(function () {
  const form = document.getElementById('admin-guide-form');
  if (!form || typeof Quill === 'undefined') return;

  const editors = [];
  document.querySelectorAll('.guide-quill-editor').forEach(function (mount) {
    const targetId = mount.getAttribute('data-target');
    const textarea = document.getElementById(targetId);
    if (!textarea) return;

    const quill = new Quill(mount, {
      theme: 'snow',
      modules: {
        toolbar: [
          [{ header: [2, 3, false] }],
          ['bold', 'italic', 'underline'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['link', 'blockquote'],
          ['clean'],
        ],
      },
    });

    if (textarea.value.trim() !== '') {
      quill.root.innerHTML = textarea.value;
    }

    editors.push({ quill: quill, textarea: textarea });
  });

  form.addEventListener('submit', function () {
    editors.forEach(function (pair) {
      pair.textarea.value = pair.quill.root.innerHTML;
    });
  });

  const titleEn = document.getElementById('title_en');
  const slugInput = document.getElementById('article_slug');
  if (titleEn && slugInput && slugInput.value.trim() === '') {
    titleEn.addEventListener('blur', function () {
      if (slugInput.value.trim() !== '') return;
      const text = titleEn.value.trim().toLowerCase();
      const slug = text
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
      if (slug) slugInput.value = slug;
    });
  }
})();
