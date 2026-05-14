/* Berlin Sleep Questionnaire — Frontend Logic */
(function () {
  'use strict';

  var TOTAL_STEPS  = 5;
  var currentStep  = 1;
  var bsqLoadedAt  = Date.now(); // used for minimum-time bot check

  /* ── DOM refs ── */
  var wrap         = document.getElementById('bsq-wrap');
  var progressFill = document.getElementById('bsq-progress-fill');
  var stepLabel    = document.getElementById('bsq-step-label');
  var prevBtn      = document.getElementById('bsq-prev');
  var nextBtn      = document.getElementById('bsq-next');
  var errorEl      = document.getElementById('bsq-error');
  var resultsEl    = document.getElementById('bsq-results');
  var resultInner  = document.getElementById('bsq-result-inner');
  var bodyEl       = document.getElementById('bsq-body');
  var navEl        = document.getElementById('bsq-nav');
  var progressWrap = document.getElementById('bsq-progress-wrap');

  if (!wrap) return;

  /* ── Style radio options with click feedback ── */
  wrap.addEventListener('change', function (e) {
    if (e.target.type !== 'radio') return;
    var group = e.target.closest('.bsq-options');
    if (!group) return;
    group.querySelectorAll('.bsq-option input').forEach(function (inp) {
      inp.closest('.bsq-option').classList.toggle('selected', inp.checked);
    });
    hideError();
  });

  /* ── Q2 conditional logic ── */
  wrap.addEventListener('change', function (e) {
    if (e.target.name !== 'q2') return;
    var show = e.target.value === 'yes';
    wrap.querySelectorAll('[data-show-if-q2]').forEach(function (el) {
      el.style.display = show ? '' : 'none';
      // clear hidden fields so they don't affect validation
      if (!show) {
        el.querySelectorAll('input[type="radio"]').forEach(function (r) { r.checked = false; });
        el.querySelectorAll('.bsq-option').forEach(function (o) { o.classList.remove('selected'); });
      }
    });
  });

  /* ── Progress update ── */
  function updateProgress() {
    var pct = (currentStep / TOTAL_STEPS) * 100;
    progressFill.style.width = pct + '%';
    stepLabel.textContent = 'Step ' + currentStep + ' of ' + TOTAL_STEPS;
    prevBtn.style.visibility = currentStep > 1 ? 'visible' : 'hidden';
    nextBtn.textContent = currentStep === TOTAL_STEPS ? 'See My Results' : 'Next';
    nextBtn.innerHTML = currentStep === TOTAL_STEPS
      ? 'See My Results <svg viewBox="0 0 20 20" fill="none"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
      : 'Next <svg viewBox="0 0 20 20" fill="none"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
  }

  /* ── Collect current step data ── */
  function collectData() {
    var data = {};
    wrap.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="number"]').forEach(function (el) {
      if (el.name) data[el.name] = el.value.trim();
    });
    wrap.querySelectorAll('select').forEach(function (el) {
      if (el.name) data[el.name] = el.value;
    });
    wrap.querySelectorAll('input[type="radio"]:checked').forEach(function (el) {
      if (el.name) data[el.name] = el.value;
    });
    // Combined height in inches for BMI
    var ft = parseInt(data['height_ft'] || 0);
    var inn = parseInt(data['height_in_val'] || data['height_in'] || 0);
    data['height_in'] = String(ft * 12 + inn);
    data['height_display'] = ft + '\'' + inn + '"';
    return data;
  }

  /* ── Validate current step ── */
  function validateStep(step) {
    var errors = [];

    if (step === 1) {
      var name  = wrap.querySelector('[name="full_name"]');
      var email = wrap.querySelector('[name="email"]');
      var phone = wrap.querySelector('[name="phone"]');
      if (!name.value.trim())                     { markInvalid(name);  errors.push('Full name is required.'); }
      if (!email.value.trim() || !email.value.includes('@')) { markInvalid(email); errors.push('A valid email address is required.'); }
      if (!phone.value.trim())                    { markInvalid(phone); errors.push('Phone number is required.'); }
    }

    if (step === 2) {
      var age    = wrap.querySelector('[name="age"]');
      var gender = wrap.querySelector('[name="gender"]:checked');
      var hft    = wrap.querySelector('[name="height_ft"]');
      var weight = wrap.querySelector('[name="weight_lbs"]');
      if (!age.value || age.value < 18)  { markInvalid(age);    errors.push('Please enter a valid age (18+).'); }
      if (!gender)                        { errors.push('Please select your gender.'); }
      if (!hft.value)                     { errors.push('Please select your height.'); }
      if (!weight.value || weight.value < 80) { markInvalid(weight); errors.push('Please enter a valid weight.'); }
    }

    if (step === 3) {
      var q2 = wrap.querySelector('[name="q2"]:checked');
      if (!q2) { errors.push('Please answer all required questions.'); }
      if (q2 && q2.value === 'yes') {
        var q3 = wrap.querySelector('[name="q3"]:checked');
        var q4 = wrap.querySelector('[name="q4"]:checked');
        if (!q3) errors.push('Please select how loud your snoring is.');
        if (!q4) errors.push('Please select how often you snore.');
      }
      var q5 = wrap.querySelector('[name="q5"]:checked');
      var q6 = wrap.querySelector('[name="q6"]:checked');
      if (q2 && q2.value === 'yes' && !q5) errors.push('Please answer whether your snoring bothers others.');
      if (!q6) errors.push('Please answer the breathing question.');
    }

    if (step === 4) {
      ['q7','q8','q9'].forEach(function (name) {
        if (!wrap.querySelector('[name="' + name + '"]:checked')) {
          errors.push('Please answer all questions on this step.');
        }
      });
    }

    if (step === 5) {
      if (!wrap.querySelector('[name="q10"]:checked')) {
        errors.push('Please answer the blood pressure question.');
      }
    }

    return errors;
  }

  function markInvalid(el) {
    el.classList.add('bsq-invalid');
    el.addEventListener('input', function () { el.classList.remove('bsq-invalid'); }, { once: true });
  }

  function showError(msg) {
    errorEl.textContent = msg;
    errorEl.style.display = 'block';
    errorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function hideError() {
    errorEl.style.display = 'none';
  }

  /* ── Navigate steps ── */
  function goToStep(step) {
    wrap.querySelectorAll('.bsq-step').forEach(function (el) {
      el.classList.toggle('active', parseInt(el.dataset.step) === step);
    });
    currentStep = step;
    updateProgress();
    hideError();
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  nextBtn.addEventListener('click', function () {
    var errs = validateStep(currentStep);
    if (errs.length) { showError(errs[0]); return; }

    if (currentStep < TOTAL_STEPS) {
      goToStep(currentStep + 1);
    } else {
      submitForm();
    }
  });

  prevBtn.addEventListener('click', function () {
    if (currentStep > 1) goToStep(currentStep - 1);
  });

  /* ── Score (mirrors PHP logic) ── */
  function scoreData(d) {
    var c1 = 0, c2 = 0;
    if (d.q2 === 'yes') c1++;
    if (['louder_than_talking','very_loud'].indexOf(d.q3 || '') > -1) c1++;
    if (['nearly_every_day','3_4_times'].indexOf(d.q4 || '') > -1) c1++;
    if (d.q5 === 'yes') c1++;
    if (['nearly_every_day','3_4_times'].indexOf(d.q6 || '') > -1) c1 += 2;

    if (['nearly_every_day','3_4_times'].indexOf(d.q7 || '') > -1) c2++;
    if (['nearly_every_day','3_4_times'].indexOf(d.q8 || '') > -1) c2++;
    if (d.q9 === 'yes') c2++;

    var wt = parseFloat(d.weight_lbs) || 0;
    var ht = parseFloat(d.height_in)  || 0;
    var bmi = ht > 0 ? (wt / (ht * ht)) * 703 : 0;

    var c3pos = (d.q10 === 'yes' || bmi > 30);
    var pos   = (c1 >= 2 ? 1 : 0) + (c2 >= 2 ? 1 : 0) + (c3pos ? 1 : 0);

    return {
      cat1: { score: c1, positive: c1 >= 2 },
      cat2: { score: c2, positive: c2 >= 2 },
      cat3: { positive: c3pos },
      bmi:  Math.round(bmi * 10) / 10,
      pos:  pos,
      high: pos >= 2,
    };
  }

  /* ── Render results ── */
  function renderResults(score) {
    var bookingUrl = (typeof BSQ !== 'undefined' && BSQ.booking_url) ? BSQ.booking_url : '/booking-method';
    var high = score.high;

    var riskBadge = high
      ? '<div class="bsq-risk-badge bsq-risk-badge--high">'
        + '<svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="2"/></svg>'
        + 'High Risk'
        + '</div>'
      : '<div class="bsq-risk-badge bsq-risk-badge--low">'
        + '<svg viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        + 'Low Risk'
        + '</div>';

    var headline = high
      ? 'Your results suggest you may be at higher risk for sleep apnea.'
      : 'Your results suggest you are currently at lower risk for sleep apnea.';

    var subtext = high
      ? 'Your responses scored positive in <strong>' + score.pos + ' out of 3</strong> risk categories. We strongly recommend scheduling a consultation — sleep apnea is treatable, and early intervention makes a real difference.'
      : 'You scored positive in <strong>' + score.pos + ' out of 3</strong> risk categories. Even at lower risk, it\'s worth discussing your sleep health with us during your next visit.';

    function catHtml(label, desc, positive) {
      var cls = positive ? 'bsq-cat bsq-cat--positive' : 'bsq-cat bsq-cat--negative';
      var icon = positive
        ? '<svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
      var statusText = positive ? 'Positive' : 'Negative';
      return '<div class="' + cls + '">'
        + '<div class="bsq-cat-status">' + icon + statusText + '</div>'
        + '<p class="bsq-cat-name">' + label + '</p>'
        + '<p class="bsq-cat-desc">' + desc + '</p>'
        + '</div>';
    }

    var cats = '<div class="bsq-categories">'
      + catHtml('Category 1', 'Snoring & breathing', score.cat1.positive)
      + catHtml('Category 2', 'Fatigue & alertness', score.cat2.positive)
      + catHtml('Category 3', 'Blood pressure / BMI', score.cat3.positive)
      + '</div>';

    var bmiLine = score.bmi > 0
      ? '<div class="bsq-bmi-row"><span>Your estimated BMI:</span> <span class="bsq-bmi-value">' + score.bmi + '</span><span style="margin-left:4px;font-size:0.8rem">' + (score.bmi > 30 ? '· Above 30 (risk factor)' : '· Within normal range for this screening') + '</span></div>'
      : '';

    var ctaBtn = high
      ? '<a href="' + bookingUrl + '" class="bsq-cta-btn bsq-cta-btn--urgent">Book a Consultation <svg viewBox="0 0 20 20" fill="none" style="width:16px;height:16px"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a><p class="bsq-cta-note">Our team will review your results and discuss next steps with you.</p>'
      : '<a href="' + bookingUrl + '" class="bsq-cta-btn">Schedule a Visit <svg viewBox="0 0 20 20" fill="none" style="width:16px;height:16px"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a><p class="bsq-cta-note">Mention your screening results when you call — we\'ll make sure to discuss your sleep health.</p>';

    resultInner.innerHTML = riskBadge
      + '<h2 class="bsq-result-headline">' + headline + '</h2>'
      + '<p class="bsq-result-sub">' + subtext + '</p>'
      + cats
      + bmiLine
      + '<div class="bsq-result-cta">' + ctaBtn + '</div>';
  }

  /* ── Submit ── */
  function submitForm() {
    var data = collectData();
    var score = scoreData(data);

    // Show results immediately (optimistic)
    bodyEl.style.display   = 'none';
    navEl.style.display    = 'none';
    progressWrap.style.display = 'none';
    renderResults(score);
    resultsEl.style.display = 'block';
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Send to backend async
    if (typeof BSQ === 'undefined' || !BSQ.ajax_url) return;

    var formData = new FormData();
    formData.append('action', 'bsq_submit');
    formData.append('nonce', BSQ.nonce);
    // Time elapsed since page load (seconds) — used server-side for bot check
    formData.append('data[_elapsed]', String(Math.floor((Date.now() - bsqLoadedAt) / 1000)));
    // Honeypot value — should always be empty
    var hp = document.querySelector('[name="website"]');
    formData.append('data[_hp]', hp ? hp.value : '');
    Object.keys(data).forEach(function (k) { formData.append('data[' + k + ']', data[k]); });

    fetch(BSQ.ajax_url, { method: 'POST', body: formData }).catch(function () {});
  }

  /* ── Init ── */
  updateProgress();

})();
