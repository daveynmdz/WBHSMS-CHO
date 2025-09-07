<?php // patientRegistration.php ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CHO - Patient Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../../css/patientRegistration.css" />
</head>

<body>
    <header>
        <div class="logo-container">
            <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_LogoClosed.png?updatedAt=1751197276128"
                alt="CHO Koronadal Logo" />
        </div>
    </header>

    <section class="homepage">
        <div class="registration-box">
            <h2>Patient Account Registration</h2>

            <div class="form-header">
                <button type="button" class="btn secondary" onclick="window.location.href='patientLogin.php'">
                    <i class="fa-solid fa-arrow-left"></i> Back to Login
                </button>
            </div>

            <!-- Live error region moved below, just above submit button -->

            <form id="registrationForm" action="registerPatient.php" method="POST" novalidate>
                <!-- CSRF placeholder (server should populate) -->
                <input type="hidden" name="csrf_token"
                    value="<?php echo isset($csrf_token) ? htmlspecialchars($csrf_token) : ''; ?>" />

                <div class="grid">
                    <div>
                        <label for="barangay">Barangay*</label>
                        <select id="barangay" name="barangay" class="input-field" required>
                            <option value="" disabled selected>Select your barangay</option>
                            <option>Brgy. Assumption</option>
                            <option>Brgy. Avanceña</option>
                            <option>Brgy. Cacub</option>
                            <option>Brgy. Caloocan</option>
                            <option>Brgy. Carpenter Hill</option>
                            <option>Brgy. Concepcion</option>
                            <option>Brgy. Esperanza</option>
                            <option>Brgy. General Paulino Santos</option>
                            <option>Brgy. Mabini</option>
                            <option>Brgy. Magsaysay</option>
                            <option>Brgy. Mambucal</option>
                            <option>Brgy. Morales</option>
                            <option>Brgy. Namnama</option>
                            <option>Brgy. New Pangasinan</option>
                            <option>Brgy. Paraiso</option>
                            <option>Brgy. Rotonda</option>
                            <option>Brgy. San Isidro</option>
                            <option>Brgy. San Roque</option>
                            <option>Brgy. San Jose</option>
                            <option>Brgy. Sta. Cruz</option>
                            <option>Brgy. Sto. Niño</option>
                            <option>Brgy. Saravia</option>
                            <option>Brgy. Topland</option>
                            <option>Brgy. Zone 1</option>
                            <option>Brgy. Zone 2</option>
                            <option>Brgy. Zone 3</option>
                            <option>Brgy. Zone 4</option>
                        </select>
                    </div>

                    <div>
                        <label for="last-name">Last Name*</label>
                        <input type="text" id="last-name" name="last_name" class="input-field" required
                            autocomplete="family-name" />
                    </div>

                    <div>
                        <label for="first-name">First Name*</label>
                        <input type="text" id="first-name" name="first_name" class="input-field" required
                            autocomplete="given-name" />
                    </div>

                    <div>
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="middle_name" class="input-field"
                            autocomplete="additional-name" />
                    </div>

                    <div>
                        <label for="suffix">Suffix</label>
                        <input type="text" id="suffix" name="suffix" placeholder="e.g. Jr., Sr., II, III"
                            class="input-field" />
                    </div>

                    <div>
                        <label for="sex">Sex*</label>
                        <select id="sex" name="sex" class="input-field" required>
                            <option value="" disabled selected>Select if Male or Female</option>
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>

                    <div>
                        <label for="date-of-birth">Date of Birth*</label>
                        <input type="date" id="date-of-birth" name="dob" class="input-field" required />
                    </div>

                    <div>
                        <label for="contact-number">Contact No.*</label>
                        <div class="contact-input-wrapper">
                            <span class="prefix">+63</span>
                            <input type="tel" id="contact-number" name="contact_num" class="input-field contact-number"
                                placeholder="### ### ####" maxlength="13" inputmode="numeric"
                                autocomplete="tel-national" required />
                        </div>
                    </div>

                    <div class="span-2">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" class="input-field" required autocomplete="email" />
                    </div>

                    <div class="password-wrapper">
                        <label for="password">Password*</label>
                        <input type="password" id="password" name="password" class="input-field" required
                            autocomplete="new-password" aria-describedby="pw-req" />
                        <i class="fa-solid fa-eye toggle-password" aria-hidden="true"></i>
                    </div>

                    <div class="password-wrapper">
                        <label for="confirm-password">Confirm Password*</label>
                        <input type="password" id="confirm-password" name="confirm_password" class="input-field"
                            required autocomplete="new-password" />
                        <i class="fa-solid fa-eye toggle-password" aria-hidden="true"></i>
                    </div>
                </div>

                <ul class="password-requirements" id="password-requirements">
                    <h4 id="pw-req">Password Requirements:</h4>
                    <li id="length"><i class="fa-solid fa-circle-xmark icon red"></i> At least 8 characters</li>
                    <li id="uppercase"><i class="fa-solid fa-circle-xmark icon red"></i> At least one uppercase letter
                    </li>
                    <li id="lowercase"><i class="fa-solid fa-circle-xmark icon red"></i> At least one lowercase letter
                    </li>
                    <li id="number"><i class="fa-solid fa-circle-xmark icon red"></i> At least one number</li>
                    <li id="match"><i class="fa-solid fa-circle-xmark icon red"></i> Passwords match</li>
                </ul>

                <div class="terms-checkbox">
                    <input type="checkbox" id="terms-check" />
                    <label for="terms-check">
                        I agree to the
                        <button type="button" id="show-terms" class="link-button">Terms &amp; Conditions</button>
                    </label>
                </div>

                <div id="error" class="error" role="alert" aria-live="polite" style="display:none"></div>
                <div class="form-footer">
                    <button id="submitBtn" type="submit" class="btn">Submit <i
                            class="fa-solid fa-arrow-right"></i></button>
                </div>
            </form>
        </div>
    </section>

    <!-- Terms Modal -->
    <div id="terms-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="terms-title">
        <div class="modal-content">
            <h2 id="terms-title">Terms &amp; Conditions</h2>
            <div class="terms-text">
                <h3>CHO Koronadal - Patient Terms and Conditions</h3>
                <p>Welcome to the City Health Office of Koronadal. By registering, you agree to provide accurate and
                    truthful information. Your data will be used solely for healthcare management purposes and will be
                    kept confidential in accordance with our privacy policy. Misuse of the system or providing false
                    information may result in account suspension or legal action. For more details, please contact the
                    City Health Office.</p>
                <p>1. By using this service, you agree...</p>
                <p>2. Your responsibilities include...</p>
                <p>3. Data privacy and security...</p>
            </div>
            <div class="modal-buttons">
                <button id="disagree-btn" class="btn secondary">I Do Not Agree</button>
                <button id="agree-btn" class="btn">I Agree</button>
            </div>
        </div>
    </div>

    <script>
        // ===== UTILITIES =====
        const $ = (sel, ctx = document) => ctx.querySelector(sel);
        const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

        // Toggle password visibility (delegated for both fields)
        document.addEventListener('click', (e) => {
            const icon = e.target.closest('.toggle-password');
            if (!icon) return;
            const input = icon.previousElementSibling;
            if (!input) return;
            const newType = input.type === 'password' ? 'text' : 'password';
            input.type = newType;
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Phone formatter + validation (PH mobile without leading 0; prefix +63)
        const phone = $('#contact-number');
        phone.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('0')) value = value.substring(1); // remove leading 0
            if (value.length > 10) value = value.slice(0, 10);
            const formatted =
                value.substring(0, 3) +
                (value.length > 3 ? ' ' + value.substring(3, 6) : '') +
                (value.length > 6 ? ' ' + value.substring(6, 10) : '');
            this.value = formatted.trim();
        });

        // Password requirements live checker
        const pw = $('#password');
        const confirmPw = $('#confirm-password');
        const reqs = {
            length: (v) => v.length >= 8,
            uppercase: (v) => /[A-Z]/.test(v),
            lowercase: (v) => /[a-z]/.test(v),
            number: (v) => /[0-9]/.test(v),
        };
        const updateReq = (li, ok) => {
            const icon = li.querySelector('i');
            if (ok) {
                icon.classList.remove('fa-circle-xmark', 'red');
                icon.classList.add('fa-circle-check', 'green');
            } else {
                icon.classList.remove('fa-circle-check', 'green');
                icon.classList.add('fa-circle-xmark', 'red');
            }
        };
        function updateAllPwReqs() {
            const v = pw.value;
            updateReq($('#length'), reqs.length(v));
            updateReq($('#uppercase'), reqs.uppercase(v));
            updateReq($('#lowercase'), reqs.lowercase(v));
            updateReq($('#number'), reqs.number(v));
            updateReq($('#match'), v && v === confirmPw.value && confirmPw.value.length > 0);
        }
        pw.addEventListener('input', updateAllPwReqs);
        confirmPw.addEventListener('input', updateAllPwReqs);

        // Terms modal wiring
        const termsModal = $('#terms-modal');
        const showTermsBtn = $('#show-terms');
        const agreeBtn = $('#agree-btn');
        const disagreeBtn = $('#disagree-btn');
        const termsCheck = $('#terms-check');
        const submitBtn = $('#submitBtn');

        // Disable submit until agree (enable during testing if desired)
        // submitBtn.disabled = true;

        showTermsBtn.addEventListener('click', () => {
            termsModal.classList.add('show');
        });
        agreeBtn.addEventListener('click', () => {
            termsModal.classList.remove('show');
            termsCheck.checked = true;
            submitBtn.disabled = false;
        });
        disagreeBtn.addEventListener('click', () => {
            termsModal.classList.remove('show');
            termsCheck.checked = false;
            submitBtn.disabled = true;
        });
        window.addEventListener('click', (e) => {
            if (e.target === termsModal) termsModal.classList.remove('show');
        });

        // DOB guardrails (no future, not older than 120 years)
        const dob = $('#date-of-birth');
        const setDobBounds = () => {
            const today = new Date();
            const max = today.toISOString().split('T')[0];
            const min = new Date(today.getFullYear() - 120, today.getMonth(), today.getDate())
                .toISOString()
                .split('T')[0];
            dob.max = max;
            dob.min = min;
        };
        setDobBounds();

        // Client-side validation + graceful submit (let native submit occur on success)
        const form = $('#registrationForm');
        const error = $('#error');

        const validBarangays = new Set([
            'Brgy. Assumption', 'Brgy. Avanceña', 'Brgy. Cacub', 'Brgy. Caloocan', 'Brgy. Carpenter Hill', 'Brgy. Concepcion', 'Brgy. Esperanza', 'Brgy. General Paulino Santos', 'Brgy. Mabini', 'Brgy. Magsaysay', 'Brgy. Mambucal', 'Brgy. Morales', 'Brgy. Namnama', 'Brgy. New Pangasinan', 'Brgy. Paraiso', 'Brgy. Rotonda', 'Brgy. San Isidro', 'Brgy. San Roque', 'Brgy. San Jose', 'Brgy. Sta. Cruz', 'Brgy. Sto. Niño', 'Brgy. Saravia', 'Brgy. Topland', 'Brgy. Zone 1', 'Brgy. Zone 2', 'Brgy. Zone 3', 'Brgy. Zone 4'
        ]);

        function showError(msg) {
            error.textContent = msg;
            error.style.display = 'block';
            // Scroll error into view for visibility
            setTimeout(() => {
                error.scrollIntoView({ behavior: 'smooth', block: 'center' });
                error.focus && error.focus();
            }, 50);
        }
        function clearError() {
            error.textContent = '';
            error.style.display = 'none';
        }

        let isSubmitting = false;
        form.addEventListener('submit', (e) => {
            clearError();

            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            // Basic requireds
            const requiredIds = ['last-name', 'first-name', 'barangay', 'sex', 'date-of-birth', 'contact-number', 'email', 'password', 'confirm-password'];
            for (const id of requiredIds) {
                const el = document.getElementById(id);
                if (!el || !el.value) {
                    e.preventDefault();
                    showError('Please fill in all required fields.');
                    return;
                }
            }

            // Terms
            if (!termsCheck.checked) {
                e.preventDefault();
                return showError('You must agree to the Terms & Conditions.');
            }

            // Barangay
            const brgy = $('#barangay').value;
            if (!validBarangays.has(brgy)) {
                e.preventDefault();
                return showError('Please select a valid barangay.');
            }

            // DOB
            if (dob.value) {
                const d = new Date(dob.value);
                const today = new Date();
                const oldest = new Date(today.getFullYear() - 120, today.getMonth(), today.getDate());
                if (d > today || d < oldest) {
                    e.preventDefault();
                    return showError('Please enter a valid date of birth.');
                }
            }

            // Phone: ensure 10 digits (after removing spaces)
            const digits = $('#contact-number').value.replace(/\D/g, '');
            if (digits.length !== 10) {
                e.preventDefault();
                return showError('Contact number must be 10 digits (e.g., 912 345 6789).');
            }

            // Email basic pattern (let server do final checks)
            const email = $('#email').value.trim();
            const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
            if (!emailOk) {
                e.preventDefault();
                return showError('Please enter a valid email address.');
            }

            // Password rules (match the visual checker)
            const p1 = pw.value;
            const p2 = confirmPw.value;
            const ok = reqs.length(p1) && reqs.uppercase(p1) && reqs.lowercase(p1) && reqs.number(p1);
            if (!ok) {
                e.preventDefault();
                return showError('Password must be at least 8 chars with uppercase, lowercase, and a number.');
            }
            if (p1 !== p2) {
                e.preventDefault();
                return showError('Passwords do not match.');
            }

            // Normalize & trim a few fields before storing
            $('#last-name').value = $('#last-name').value.trim();
            $('#first-name').value = $('#first-name').value.trim();
            $('#middle-name').value = $('#middle-name').value.trim();
            $('#suffix').value = $('#suffix').value.trim();
            $('#contact-number').value = digits; // store only digits

            // Optional: store non-sensitive fields in sessionStorage
            const registrationData = {
                last_name: $('#last-name').value,
                first_name: $('#first-name').value,
                middle_name: $('#middle-name').value,
                suffix: $('#suffix').value,
                barangay: $('#barangay').value,
                sex: $('#sex').value,
                date_of_birth: $('#date-of-birth').value,
                contact_number: $('#contact-number').value,
                email: $('#email').value
            };
            try { sessionStorage.setItem('registrationData', JSON.stringify(registrationData)); } catch (_) { }

            // Double-submit guard: disable button and allow native submit
            isSubmitting = true;
            submitBtn.disabled = true;
            // IMPORTANT: do NOT call e.preventDefault() here. Let the browser post to registerPatient.php.
        });
    </script>
</body>

</html>