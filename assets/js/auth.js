(function () {
  function togglePassword(id) {
    const input = document.getElementById(id);
    if (!input) return;
    input.type = (input.type === "password") ? "text" : "password";
  }

  window.togglePassword = togglePassword;

  const pass1 = document.getElementById("password");
  const pass2 = document.getElementById("password2");
  const rulesBox = document.getElementById("passwordRules");
  const matchEl  = document.getElementById("passwordMatch");

  const rules = {
    length: v => v.length >= 8,
    upper:  v => /[A-Z]/.test(v),
    lower:  v => /[a-z]/.test(v),
    number: v => /[0-9]/.test(v)
  };

  function actualizarReglas() {
    if (!pass1 || !rulesBox) return;
    const v = pass1.value;

    Object.keys(rules).forEach(key => {
     const el = document.querySelector(`.regla[data-rule="${key}"]`);
     if (!el) return;

      const ok = rules[key](v);
      el.classList.toggle("ok", ok);
      el.classList.toggle("bad", !ok);
    });
}

  function updateMatch() {
    if (!pass1 || !pass2 || !matchEl) return;

    const a = pass1.value;
    const b = pass2.value;

    if (b.length === 0) {
      matchEl.className = "coincidencia";
      matchEl.textContent = "";
      return;
    }

    const ok = (a === b);
    matchEl.className = "coincidencia show " + (ok ? "ok" : "bad");
    matchEl.textContent = ok ? "La contraseña coincide" : "La contraseña no coincide";
  }

  window.actualizarReglas = actualizarReglas;
  window.updateMatch = updateMatch;

  if (pass1 && rulesBox) {
    pass1.addEventListener("focus", () => { rulesBox.classList.add("show"); actualizarReglas(); });
    pass1.addEventListener("input", () => { rulesBox.classList.add("show"); actualizarReglas(); updateMatch(); });
    pass1.addEventListener("blur",  () => { if (pass1.value.length === 0) rulesBox.classList.remove("show"); });
  }

  if (pass2 && matchEl) {
    pass2.addEventListener("focus", () => { matchEl.classList.add("show"); updateMatch(); });
    pass2.addEventListener("input", () => { matchEl.classList.add("show"); updateMatch(); });
    pass2.addEventListener("blur",  () => {
      if (pass2.value.length === 0) { matchEl.className = "coincidencia"; matchEl.textContent = ""; }
    });
  }

  const forms = [
    { formId: "registerForm", btnId: "registerBtn", text: "Creando..." },
    { formId: "resetForm",    btnId: "resetBtn",    text: "Guardando..." },
  ];

  forms.forEach(({ formId, btnId, text }) => {
    const form = document.getElementById(formId);
    const btn  = document.getElementById(btnId);
    if (!form || !btn) return;

    form.addEventListener("submit", () => {
      btn.disabled = true;
      btn.textContent = text;
    });
  });
})();
