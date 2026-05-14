<!-- Honeypot trap — visually hidden, bots fill it, humans don't -->
<div class="bsq-hp" aria-hidden="true">
  <label for="bsq-hp-field">Leave this blank</label>
  <input type="text" id="bsq-hp-field" name="website" value="" tabindex="-1" autocomplete="off" />
</div>

<div id="bsq-wrap" role="main">

  <!-- ── Header ── -->
  <div class="bsq-header">
    <div class="bsq-header-icon">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="24" cy="24" r="20" fill="rgba(255,255,255,0.15)"/>
        <path d="M16 22c0-4.4 3.6-8 8-8s8 3.6 8 8c0 3-1.5 6-3 8-.7 1-1.3 2-2 2h-6c-.7 0-1.3-1-2-2-1.5-2-3-5-3-8Z" fill="white" opacity="0.9"/>
        <path d="M20 28h8M22 32h4" stroke="rgba(45,106,90,0.6)" stroke-width="2" stroke-linecap="round"/>
        <path d="M12 36c0-2 3-4 6-4h12c3 0 6 2 6 4" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.6"/>
      </svg>
    </div>
    <div>
      <h1 class="bsq-title">Sleep Apnea Risk Assessment</h1>
      <p class="bsq-subtitle">The Berlin Questionnaire · Takes about 3 minutes</p>
    </div>
  </div>

  <!-- ── Progress ── -->
  <div class="bsq-progress-wrap" id="bsq-progress-wrap">
    <div class="bsq-progress-track">
      <div class="bsq-progress-fill" id="bsq-progress-fill"></div>
    </div>
    <p class="bsq-step-label" id="bsq-step-label">Step 1 of 5</p>
  </div>

  <!-- ── Steps ── -->
  <div class="bsq-body" id="bsq-body">

    <!-- STEP 1: Contact -->
    <div class="bsq-step active" data-step="1">
      <div class="bsq-step-intro">
        <span class="bsq-step-badge">Contact Info</span>
        <h2>Let's start with your details</h2>
        <p>Your information is private and used only to follow up on your results.</p>
      </div>

      <div class="bsq-fields">
        <div class="bsq-field">
          <label for="bsq-full-name">Full Name <span class="req">*</span></label>
          <input type="text" id="bsq-full-name" name="full_name" placeholder="Jane Smith" autocomplete="name" />
        </div>
        <div class="bsq-field-row">
          <div class="bsq-field">
            <label for="bsq-email">Email Address <span class="req">*</span></label>
            <input type="email" id="bsq-email" name="email" placeholder="jane@email.com" autocomplete="email" />
          </div>
          <div class="bsq-field">
            <label for="bsq-phone">Phone Number <span class="req">*</span></label>
            <input type="tel" id="bsq-phone" name="phone" placeholder="(519) 000-0000" autocomplete="tel" />
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 2: Profile -->
    <div class="bsq-step" data-step="2">
      <div class="bsq-step-intro">
        <span class="bsq-step-badge">Your Profile</span>
        <h2>A bit about you</h2>
        <p>Height and weight help calculate your BMI, which is part of the screening criteria.</p>
      </div>

      <div class="bsq-fields">
        <div class="bsq-field-row">
          <div class="bsq-field">
            <label for="bsq-age">Age <span class="req">*</span></label>
            <input type="number" id="bsq-age" name="age" min="18" max="100" placeholder="e.g. 45" />
          </div>
          <div class="bsq-field">
            <label>Gender <span class="req">*</span></label>
            <div class="bsq-options bsq-options--2col" data-name="gender">
              <label class="bsq-option"><input type="radio" name="gender" value="male" /><span>Male</span></label>
              <label class="bsq-option"><input type="radio" name="gender" value="female" /><span>Female</span></label>
            </div>
          </div>
        </div>

        <div class="bsq-field-row">
          <div class="bsq-field">
            <label>Height <span class="req">*</span></label>
            <div class="bsq-height-row">
              <select name="height_ft" id="bsq-height-ft">
                <option value="">ft</option>
                <?php for ($i = 4; $i <= 7; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?> ft</option>
                <?php endfor; ?>
              </select>
              <select name="height_in" id="bsq-height-in">
                <option value="">in</option>
                <?php for ($i = 0; $i <= 11; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?> in</option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <div class="bsq-field">
            <label for="bsq-weight">Weight (lbs) <span class="req">*</span></label>
            <input type="number" id="bsq-weight" name="weight_lbs" min="80" max="500" placeholder="e.g. 185" />
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 3: Snoring & Breathing -->
    <div class="bsq-step" data-step="3">
      <div class="bsq-step-intro">
        <span class="bsq-step-badge">Snoring & Breathing</span>
        <h2>About your sleep at night</h2>
        <p>Answer as accurately as possible — ask a partner if you're not sure.</p>
      </div>

      <div class="bsq-questions">

        <div class="bsq-question">
          <p class="bsq-q-label">Do you snore? <span class="req">*</span></p>
          <div class="bsq-options bsq-options--3col" data-name="q2">
            <label class="bsq-option"><input type="radio" name="q2" value="yes" /><span>Yes</span></label>
            <label class="bsq-option"><input type="radio" name="q2" value="no" /><span>No</span></label>
            <label class="bsq-option"><input type="radio" name="q2" value="dont_know" /><span>Don't Know</span></label>
          </div>
        </div>

        <div class="bsq-question bsq-conditional" data-show-if-q2="yes" style="display:none">
          <p class="bsq-q-label">Your snoring is… <span class="req">*</span></p>
          <div class="bsq-options" data-name="q3">
            <label class="bsq-option"><input type="radio" name="q3" value="slightly_louder" /><span>Slightly louder than breathing</span></label>
            <label class="bsq-option"><input type="radio" name="q3" value="as_loud_talking" /><span>As loud as talking</span></label>
            <label class="bsq-option"><input type="radio" name="q3" value="louder_than_talking" /><span>Louder than talking</span></label>
            <label class="bsq-option"><input type="radio" name="q3" value="very_loud" /><span>Very loud — can be heard in adjacent rooms</span></label>
          </div>
        </div>

        <div class="bsq-question bsq-conditional" data-show-if-q2="yes" style="display:none">
          <p class="bsq-q-label">How often do you snore? <span class="req">*</span></p>
          <div class="bsq-options" data-name="q4">
            <label class="bsq-option"><input type="radio" name="q4" value="nearly_every_day" /><span>Nearly every day</span></label>
            <label class="bsq-option"><input type="radio" name="q4" value="3_4_times" /><span>3–4 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q4" value="1_2_times_week" /><span>1–2 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q4" value="1_2_times_month" /><span>1–2 times a month</span></label>
            <label class="bsq-option"><input type="radio" name="q4" value="never" /><span>Never or nearly never</span></label>
          </div>
        </div>

        <div class="bsq-question bsq-conditional" data-show-if-q2="yes" style="display:none">
          <p class="bsq-q-label">Has your snoring ever bothered other people? <span class="req">*</span></p>
          <div class="bsq-options bsq-options--2col" data-name="q5">
            <label class="bsq-option"><input type="radio" name="q5" value="yes" /><span>Yes</span></label>
            <label class="bsq-option"><input type="radio" name="q5" value="no" /><span>No</span></label>
          </div>
        </div>

        <div class="bsq-question">
          <p class="bsq-q-label">Has anyone noticed that you stop breathing during sleep? <span class="req">*</span></p>
          <div class="bsq-options" data-name="q6">
            <label class="bsq-option"><input type="radio" name="q6" value="nearly_every_day" /><span>Nearly every day</span></label>
            <label class="bsq-option"><input type="radio" name="q6" value="3_4_times" /><span>3–4 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q6" value="1_2_times_week" /><span>1–2 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q6" value="1_2_times_month" /><span>1–2 times a month</span></label>
            <label class="bsq-option"><input type="radio" name="q6" value="never" /><span>Never or nearly never</span></label>
          </div>
        </div>

      </div>
    </div>

    <!-- STEP 4: Energy & Alertness -->
    <div class="bsq-step" data-step="4">
      <div class="bsq-step-intro">
        <span class="bsq-step-badge">Energy & Alertness</span>
        <h2>How do you feel day to day?</h2>
        <p>Think about the past few months when answering.</p>
      </div>

      <div class="bsq-questions">

        <div class="bsq-question">
          <p class="bsq-q-label">How often do you feel tired or fatigued <em>after</em> sleeping? <span class="req">*</span></p>
          <div class="bsq-options" data-name="q7">
            <label class="bsq-option"><input type="radio" name="q7" value="nearly_every_day" /><span>Nearly every day</span></label>
            <label class="bsq-option"><input type="radio" name="q7" value="3_4_times" /><span>3–4 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q7" value="1_2_times_week" /><span>1–2 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q7" value="1_2_times_month" /><span>1–2 times a month</span></label>
            <label class="bsq-option"><input type="radio" name="q7" value="never" /><span>Never or nearly never</span></label>
          </div>
        </div>

        <div class="bsq-question">
          <p class="bsq-q-label">During your waking hours, do you feel tired, fatigued, or not up to par? <span class="req">*</span></p>
          <div class="bsq-options" data-name="q8">
            <label class="bsq-option"><input type="radio" name="q8" value="nearly_every_day" /><span>Nearly every day</span></label>
            <label class="bsq-option"><input type="radio" name="q8" value="3_4_times" /><span>3–4 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q8" value="1_2_times_week" /><span>1–2 times a week</span></label>
            <label class="bsq-option"><input type="radio" name="q8" value="1_2_times_month" /><span>1–2 times a month</span></label>
            <label class="bsq-option"><input type="radio" name="q8" value="never" /><span>Never or nearly never</span></label>
          </div>
        </div>

        <div class="bsq-question">
          <p class="bsq-q-label">Have you ever nodded off or fallen asleep while driving a vehicle? <span class="req">*</span></p>
          <div class="bsq-options bsq-options--2col" data-name="q9">
            <label class="bsq-option"><input type="radio" name="q9" value="yes" /><span>Yes</span></label>
            <label class="bsq-option"><input type="radio" name="q9" value="no" /><span>No</span></label>
          </div>
        </div>

      </div>
    </div>

    <!-- STEP 5: Health History -->
    <div class="bsq-step" data-step="5">
      <div class="bsq-step-intro">
        <span class="bsq-step-badge">Health History</span>
        <h2>One last question</h2>
        <p>This is the final step before we calculate your results.</p>
      </div>

      <div class="bsq-questions">
        <div class="bsq-question">
          <p class="bsq-q-label">Do you have high blood pressure (or are you being treated for it)? <span class="req">*</span></p>
          <div class="bsq-options bsq-options--3col" data-name="q10">
            <label class="bsq-option"><input type="radio" name="q10" value="yes" /><span>Yes</span></label>
            <label class="bsq-option"><input type="radio" name="q10" value="no" /><span>No</span></label>
            <label class="bsq-option"><input type="radio" name="q10" value="dont_know" /><span>Don't Know</span></label>
          </div>
        </div>
      </div>

    </div>

  </div><!-- /bsq-body -->

  <!-- ── Error message ── -->
  <div class="bsq-error" id="bsq-error" style="display:none"></div>

  <!-- ── Navigation ── -->
  <div class="bsq-nav" id="bsq-nav">
    <button type="button" class="bsq-btn bsq-btn--ghost" id="bsq-prev" style="visibility:hidden">
      <svg viewBox="0 0 20 20" fill="none"><path d="M12 15l-5-5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Back
    </button>
    <button type="button" class="bsq-btn bsq-btn--primary" id="bsq-next">
      Next
      <svg viewBox="0 0 20 20" fill="none"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>

  <!-- ── Results ── -->
  <div class="bsq-results" id="bsq-results" style="display:none">

    <div class="bsq-result-inner" id="bsq-result-inner">
      <!-- Populated by JS -->
    </div>

    <p class="bsq-disclaimer">
      This screening tool is not a medical diagnosis. Results are based on the validated Berlin Questionnaire used by healthcare providers worldwide. A qualified professional can provide a formal evaluation.
    </p>

  </div>

</div><!-- /bsq-wrap -->
