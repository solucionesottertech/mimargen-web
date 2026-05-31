/**
 * Calculator Widget - Vanilla JS
 * Targets landing.php calculator HTML structure.
 */
(function () {
  'use strict';

  // Rate limiting config
  const LIMITS = { daily: 3, weekly: 15, monthly: 40 };
  const STORAGE_KEY = 'mimargen_calc_usage';
  const MAX_INGREDIENTS = 10;

  function getUsage() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return { daily: 0, weekly: 0, monthly: 0, lastReset: Date.now() };
      return JSON.parse(raw);
    } catch {
      return { daily: 0, weekly: 0, monthly: 0, lastReset: Date.now() };
    }
  }

  function resetIfNeeded(usage) {
    const now = Date.now();
    const last = usage.lastReset;
    const dayMs = 24 * 60 * 60 * 1000;
    const weekMs = 7 * dayMs;
    const monthMs = 30 * dayMs;
    if (now - last > monthMs) {
      usage.daily = 0;
      usage.weekly = 0;
      usage.monthly = 0;
      usage.lastReset = now;
    } else if (now - last > weekMs) {
      usage.daily = 0;
      usage.weekly = 0;
      usage.lastReset = now;
    } else if (now - last > dayMs) {
      usage.daily = 0;
      usage.lastReset = now;
    }
    return usage;
  }

  function canCalculate() {
    const usage = resetIfNeeded(getUsage());
    return usage.daily < LIMITS.daily && usage.weekly < LIMITS.weekly && usage.monthly < LIMITS.monthly;
  }

  function recordCalculation() {
    const usage = resetIfNeeded(getUsage());
    usage.daily++;
    usage.weekly++;
    usage.monthly++;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(usage));
    return usage;
  }

  function updateCounter() {
    const usage = resetIfNeeded(getUsage());
    const remaining = Math.max(0, LIMITS.daily - usage.daily);
    const el = document.getElementById('calc-usage-counter');
    if (el) el.textContent = String(remaining);
    const notice = document.getElementById('calc-rate-limit-notice');
    if (remaining <= 0) {
      notice?.classList.remove('hidden');
    } else {
      notice?.classList.add('hidden');
    }
  }

  function formatCLP(n) {
    return '$' + Math.round(n).toLocaleString('es-CL');
  }

  function createIngredientRow() {
    const container = document.getElementById('calc-ingredients');
    if (!container) return;
    const rows = container.querySelectorAll('.ingredient-row');
    if (rows.length >= MAX_INGREDIENTS) return;

    const row = document.createElement('div');
    row.className = 'ingredient-row grid grid-cols-12 gap-2 items-center';
    row.innerHTML =
      '<div class="col-span-5">' +
      '<input type="text" placeholder="Ingrediente" class="ing-name w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />' +
      '</div>' +
      '<div class="col-span-2">' +
      '<input type="number" placeholder="500" min="0" step="any" class="ing-qty w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />' +
      '</div>' +
      '<div class="col-span-3">' +
      '<input type="number" placeholder="1.200" min="0" step="any" class="ing-price w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition" />' +
      '</div>' +
      '<div class="col-span-2 flex items-end">' +
      '<button type="button" class="remove-ing w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition flex items-center justify-center" aria-label="Eliminar ingrediente">' +
      '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>' +
      '</button>' +
      '</div>';

    row.querySelector('.remove-ing').addEventListener('click', function () {
      row.remove();
      updateRemoveButtons();
    });

    container.appendChild(row);
    updateRemoveButtons();
  }

  function updateRemoveButtons() {
    const container = document.getElementById('calc-ingredients');
    if (!container) return;
    const rows = container.querySelectorAll('.ingredient-row');
    rows.forEach(function (row) {
      var btn = row.querySelector('.remove-ing');
      if (btn) btn.classList.toggle('hidden', rows.length <= 1);
    });
    var addBtn = document.getElementById('calc-add-ingredient');
    if (addBtn) addBtn.classList.toggle('hidden', rows.length >= MAX_INGREDIENTS);
  }

  function calculate() {
    if (!canCalculate()) {
      updateCounter();
      return;
    }

    var container = document.getElementById('calc-ingredients');
    var rows = container ? container.querySelectorAll('.ingredient-row') : [];
    var materialsCost = 0;
    rows.forEach(function (row) {
      var qty = parseFloat(row.querySelector('.ing-qty').value || '0');
      var price = parseFloat(row.querySelector('.ing-price').value || '0');
      materialsCost += qty * price;
    });

    var laborPerHour = parseFloat(document.getElementById('calc-labor').value || '0');
    var laborHours = parseFloat(document.getElementById('calc-hours').value || '0');
    var wastePct = parseFloat(document.getElementById('calc-waste').value || '0') / 100;
    var units = parseInt(document.getElementById('calc-units').value || '1', 10);

    var laborCost = laborPerHour * laborHours;
    var materialsAdjusted = materialsCost * (1 + wastePct);
    var totalCost = materialsAdjusted + laborCost;
    var costPerUnit = units > 0 ? totalCost / units : 0;
    var suggestedPrice = costPerUnit / (1 - 0.5);
    var marginPerUnit = suggestedPrice - costPerUnit;

    var resMaterials = document.getElementById('res-materials');
    var resLabor = document.getElementById('res-labor');
    var resWaste = document.getElementById('res-waste');
    var resTotal = document.getElementById('res-total');
    var resUnit = document.getElementById('res-unit');
    var resMargin = document.getElementById('res-margin');

    if (resMaterials) resMaterials.textContent = formatCLP(materialsAdjusted);
    if (resLabor) resLabor.textContent = formatCLP(laborCost);
    if (resWaste) resWaste.textContent = formatCLP(materialsCost * wastePct);
    if (resTotal) resTotal.textContent = formatCLP(totalCost);
    if (resUnit) resUnit.textContent = formatCLP(costPerUnit);
    if (resMargin) resMargin.textContent = 'Margen sugerido (50%): ' + formatCLP(marginPerUnit) + ' por unidad — Precio: ' + formatCLP(suggestedPrice);

    var results = document.getElementById('calc-results');
    if (results) results.classList.remove('hidden');

    recordCalculation();
    updateCounter();
  }

  function init() {
    var addBtn = document.getElementById('calc-add-ingredient');
    var calcBtn = document.getElementById('calc-calculate');

    if (addBtn) addBtn.addEventListener('click', createIngredientRow);
    if (calcBtn) calcBtn.addEventListener('click', calculate);

    updateCounter();
    updateRemoveButtons();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
