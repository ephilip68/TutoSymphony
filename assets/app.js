

/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import Choices from 'choices.js';

/******************************************* Statistique *********************************************/  

document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200; // Plus grand = animation plus lente

    counters.forEach(counter => {
        const targetValue = +counter.getAttribute('data-count');
        let currentValue = 0;

        const animate = () => {
            const increment = Math.max(1, Math.ceil(targetValue / speed)); 
            // Toujours au moins 1 pour éviter les blocages

            if (currentValue < targetValue) {
                currentValue += increment;
                if (currentValue > targetValue) currentValue = targetValue;
                counter.innerText = currentValue;
                requestAnimationFrame(animate); // Plus fluide que setTimeout
            } else {
                counter.innerText = targetValue;
            }
        };

        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                animate();
                observer.disconnect();
            }
        }, { threshold: 0.5 });

        observer.observe(counter);
    });

    // Gestion des fade-in
    const faders = document.querySelectorAll('.fade-in-section');

    const appearOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    };

    const appearOnScroll = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, appearOptions);

    faders.forEach(fader => appearOnScroll.observe(fader));
});

        
/********************************************* Formulaire ***************************************************/

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

/*********************************************** message d'erreur *************************************************/

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('newsletter-form');
    const messages = document.getElementById('newsletter-messages');
    const submitBtn = document.getElementById('newsletter-submit');
    const spinner = document.getElementById('newsletter-spinner');
    const btnText = submitBtn.querySelector('.btn-text');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Afficher le spinner et désactiver le bouton
        spinner.classList.remove('d-none');
        btnText.textContent = 'Envoi...';
        submitBtn.disabled = true;

        const formData = new FormData(form);

        fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(response => response.json())
        .then(data => {
            messages.innerHTML = `<div class="alert alert-${data.status} mt-2">${data.message}</div>`;

            if (data.status === 'success') {
                form.reset();
            }
        })
        .catch(err => {
            messages.innerHTML = `<div class="alert alert-danger mt-2">Une erreur est survenue, réessayez.</div>`;
            console.error(err);
        })
        .finally(() => {
            // Masquer le spinner et réactiver le bouton
            spinner.classList.add('d-none');
            btnText.textContent = "S'abonner";
            submitBtn.disabled = false;
        });
    });
});

/********************************************* Pagination Ajax ************************************/

document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('load-more-recipes');
    const recipesContainer = document.getElementById('recipe-list');
    const loading = document.getElementById('loading');

    if (!loadMoreBtn || !recipesContainer) return;

    loadMoreBtn.addEventListener('click', function() {
        let currentPage = parseInt(recipesContainer.dataset.currentPage);
        const maxPage = parseInt(recipesContainer.dataset.maxPage);
        const dataUrl = recipesContainer.dataset.url;

        if (currentPage >= maxPage) {
            loadMoreBtn.disabled = true;
            loadMoreBtn.querySelector('.btn-text').textContent = "Toutes les recettes chargées";
            return;
        }

        // Spinner ON
        loading.style.display = 'block';
        loadMoreBtn.disabled = true;
        loadMoreBtn.querySelector('.btn-text').textContent = "Chargement...";

        const nextPage = currentPage + 1;

        fetch(`${dataUrl}?page=${nextPage}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // 🔥 On récupère uniquement les nouvelles recettes
            const newRecipes = tempDiv.querySelectorAll('.recipe-card');

            newRecipes.forEach(recipe => recipesContainer.appendChild(recipe));

            // Mettre à jour la page actuelle
            recipesContainer.dataset.currentPage = nextPage;

            // Spinner OFF
            loading.style.display = 'none';
            loadMoreBtn.disabled = false;
            loadMoreBtn.querySelector('.btn-text').textContent = "🍃 Charger plus de recettes";

            if (nextPage >= maxPage) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.querySelector('.btn-text').textContent = "Toutes les recettes chargées";
            }
        })
        .catch(error => {
            console.error(error);
            loading.style.display = 'none';
            loadMoreBtn.disabled = false;
            loadMoreBtn.querySelector('.btn-text').textContent = "🍃 Charger plus de recettes";
        });
    });
});

/************************************************** Select Perso *************************************************************/
document.addEventListener('DOMContentLoaded', function() {
    const seasonSelect = document.getElementById('recipe_seasons');
    if (!seasonSelect) return;

    const placeholderText = 'Choisir une ou plusieurs saisons';

    // --- 1️⃣ Ajouter le placeholder en option désactivée ---
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholderText;
    placeholderOption.disabled = true;
    placeholderOption.selected = !seasonSelect.querySelector('option[selected]');
    seasonSelect.insertBefore(placeholderOption, seasonSelect.firstChild);

    // --- 2️⃣ Initialisation de Choices ---
    const choices = new Choices(seasonSelect, {
        removeItemButton: true,
        placeholder: true,
        placeholderValue: placeholderText,
        searchEnabled: false,
        shouldSort: false,
        allowHTML: true,
        itemSelectText: '', // supprime "Appuyez pour sélectionner"
    });

    const container = seasonSelect.closest('.choices').querySelector('.choices__inner');
    const input = container.querySelector('.choices__input--cloned');
    const dropdownList = () => container.querySelector('.choices__list--dropdown');
    const hasValue = () => choices.getValue(true).length > 0;

    // --- 3️⃣ Gérer le placeholder dans l’input ---
    const updatePlaceholder = () => {
        if (!input) return;
        input.placeholder = hasValue() ? '' : placeholderText;
    };

    // Initialisation
    updatePlaceholder();

    // Mise à jour lors de l’ajout ou suppression de tags
    choices.passedElement.element.addEventListener('addItem', updatePlaceholder);
    choices.passedElement.element.addEventListener('removeItem', updatePlaceholder);

    // --- 4️⃣ Gestion du menu déroulant ---
    const openDropdown = () => {
        choices.showDropdown();
        container.classList.add('is-open', 'is-focused');
        if (input) input.focus();
    };

    const closeDropdown = () => {
        choices.hideDropdown();
        container.classList.remove('is-open', 'is-focused');
    };

    const toggleDropdown = () => {
        container.classList.contains('is-open') ? closeDropdown() : openDropdown();
    };

    container.addEventListener('click', function(e) {
        if (dropdownList() && dropdownList().contains(e.target)) return;
        if (e.target.closest('.choices__button')) return; // suppression de tag
        e.preventDefault();
        e.stopPropagation();
        toggleDropdown();
    });

    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) closeDropdown();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDropdown();
    });
});

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("select.form-select:not([multiple])").forEach((el) => {
        const choices = new Choices(el, {
            searchEnabled: false,       // désactive la recherche pour un menu déroulant simple
            itemSelectText: "",         // supprime le texte "Appuyer pour sélectionner"
            shouldSort: false,          // garde l’ordre d’origine
            position: "auto",           // dropdown bien placée
            classNames: {
                containerOuter: 'form-select-choices', // UN seul nom de classe !
            }
        });

        // --- Survol sur le premier élément sélectionnable au menu ---
        const container = el.closest('.form-select-choices');
        el.addEventListener('showDropdown', () => {
            const list = container.querySelector('.choices__list--dropdown');
            if (!list) return;
            const firstSelectable = list.querySelector('.choices__item:not(.choices__placeholder)');
            if (firstSelectable) {
                list.querySelectorAll('.is-highlighted').forEach(item => item.classList.remove('is-highlighted'));
                firstSelectable.classList.add('is-highlighted');
            }
        });
    });
});

document.addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-favorite');
  if (!btn) return;

  e.preventDefault();

  const url = btn.dataset.url;
  const csrf = btn.dataset.csrf;
  const icon = btn.querySelector('i');
  const isFavorited = icon.classList.contains('fas'); // cœur plein = favori

  // 💡 Feedback visuel instantané
  icon.classList.toggle('fas', !isFavorited);
  icon.classList.toggle('far', isFavorited);

  // Empêche le spam clic
  btn.classList.add('disabled');

  fetch(url, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrf,
      'Content-Type': 'application/json'
    }
  })
    .then(r => r.json())
    .then(data => {
      if (!data || data.favorited === undefined) {
        console.error('Réponse inattendue', data);
        // ❌ On rétablit si erreur
        icon.classList.toggle('fas', isFavorited);
        icon.classList.toggle('far', !isFavorited);
        return;
      }

      // ✅ Met à jour l’état visuel du bouton
      icon.closest('.btn-favorite').classList.toggle('active', data.favorited);

      // 💥 Effet "pop" au moment du like
      btn.classList.remove('pop');
      void btn.offsetWidth; // force le reflow pour relancer l’animation
      btn.classList.add('pop');

      // ✅ Mettre à jour le compteur
      const counter = document.getElementById('favorite-count');
      if (counter) {
        let count = parseInt(counter.textContent);
        if (data.favorited) {
          counter.textContent = count + 1;
        } else {
          counter.textContent = Math.max(0, count - 1);
        }

        // 💥 Animation pop sur le badge compteur
        const badge = counter.closest('.favorite-count-badge');
        if (badge) {
          badge.classList.remove('pop');
          void badge.offsetWidth;
          badge.classList.add('pop');
        }
      }
    })
    .catch(err => {
      console.error(err);
      // ❌ On rétablit si erreur
      icon.classList.toggle('fas', isFavorited);
      icon.classList.toggle('far', !isFavorited);
    })
    .finally(() => {
      btn.classList.remove('disabled');
    });
});



 console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
