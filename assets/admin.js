

import './styles/admin.css';


document.addEventListener('DOMContentLoaded', function () {

    /* ========== 1. Gestion des champs remplis (effet "filled") ========== */
    function checkFilled(el) {
        if (el.type === "file") {
            el.classList.toggle('filled', el.files.length > 0);
        } else {
            el.classList.toggle('filled', el.value.trim() !== "");
        }
    }

    document.querySelectorAll('input.form-control, textarea.form-control, select.form-select')
        .forEach(input => {
            checkFilled(input);
            input.addEventListener('blur', () => checkFilled(input));
            input.addEventListener('change', () => checkFilled(input));
        });

    /* ========== 2. Empêcher les valeurs négatives (durée + quantités) ========== */
    function sanitizeNumericField(field) {
        field.setAttribute("type", "number");
        field.setAttribute("min", "0");
        if (!field.value || parseInt(field.value) < 0) field.value = 0;
        field.addEventListener("input", () => {
            if (field.value < 0) field.value = 0;
        });
    }

    document.querySelectorAll('input[name*="duration"], input[name*="quantity"]')
        .forEach(sanitizeNumericField);

    /* ========== 3. Gestion dynamique des ingrédients ========== */
    let wrapper = document.querySelector('#ingredients-wrapper');
    if (!wrapper) return;

    let addBtn = document.querySelector('#add-ingredient');      // bouton "Ajouter ingrédient"
    let addCustomBtn = document.querySelector('#add-ingredient-btn'); // bouton "Nouvel ingrédient perso"
    let inputCustom = document.querySelector('#new-ingredient'); // champ texte ingrédient perso
    let countSpan = document.querySelector('#ingredient-count'); // compteur
    let index = wrapper.querySelectorAll('.ingredient-item').length;

    function updateCount() {
        if (countSpan) countSpan.textContent = wrapper.querySelectorAll('.ingredient-item').length;
    }

    function handleRemove(button) {
        if (!button) return;
        button.addEventListener('click', () => {
            button.closest('.ingredient-item').remove();
            updateCount();
        });
    }

    // ➕ Ajouter ingrédient via prototype (select existant du form Symfony)
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            let prototype = wrapper.dataset.prototype;
            let newForm = prototype.replace(/__name__/g, index);
            index++;

            let div = document.createElement('div');
            div.classList.add('ingredient-item', 'shadow-sm', 'mb-3');
            div.innerHTML = newForm + `
                <button type="button" class="remove-ingredient btn btn-sm mt-2">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </button>`;

            wrapper.appendChild(div);

            // Style correct pour les nouveaux selects
            div.querySelectorAll('select').forEach(sel => sel.classList.add('form-select', 'shadow-sm'));

            // Anti-négatif pour les quantités
            div.querySelectorAll('input[name*="quantity"]').forEach(sanitizeNumericField);

            // Bouton suppression
            handleRemove(div.querySelector('.remove-ingredient'));

            updateCount();
        });
    }

    // 🛒 Ajouter un ingrédient personnalisé
    if (addCustomBtn) {
        addCustomBtn.addEventListener('click', function () {
            let newIngredientName = inputCustom.value.trim();
            if (newIngredientName === "") return;

            let div = document.createElement('div');
            div.classList.add('ingredient-item', 'shadow-sm', 'mb-3');
            div.innerHTML = `
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-basket-shopping me-2" style="color:#7ba77b"></i> Ingrédient
                    </label>
                    <input type="text" name="custom_ingredients[${index}][name]" 
                        value="${newIngredientName}" 
                        class="form-control shadow-sm" />
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-balance-scale me-2" style="color:#7ba77b"></i> Quantité
                    </label>
                    <input type="number" min="0" value="0" 
                        name="custom_ingredients[${index}][quantity]" 
                        class="form-control shadow-sm" placeholder="ex. 200" />
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-ruler me-2" style="color:#7ba77b"></i> Unité
                    </label>
                    <input type="text" name="custom_ingredients[${index}][unit]" 
                        class="form-control shadow-sm" placeholder="ex. g, ml, pièces" />
                </div>
                <button type="button" class="remove-ingredient btn btn-sm mt-2">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </button>`;

            wrapper.appendChild(div);

            // Appliquer "filled" automatiquement sur les inputs
            div.querySelectorAll('input.form-control').forEach(f => {
                checkFilled(f);
                f.addEventListener('blur', () => checkFilled(f));
                f.addEventListener('change', () => checkFilled(f));
            });

            // Anti-négatif pour quantité
            div.querySelectorAll('input[name*="quantity"]').forEach(sanitizeNumericField);

            // Bouton suppression
            handleRemove(div.querySelector('.remove-ingredient'));

            updateCount();
            inputCustom.value = ""; // reset champ texte

            // 🔑 INCRÉMENTER L’INDEX pour le prochain ingrédient
            index++;
        });
    }

    // Initialiser suppression sur les ingrédients déjà présents
    wrapper.querySelectorAll('.remove-ingredient').forEach(handleRemove);
    updateCount();
});

