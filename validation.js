(() => {
	function byId(id) { return document.getElementById(id); }
	function showNotice(message, type = 'error') {
		const el = document.getElementById('clientNotice');
		if (!el) return;
		el.classList.remove('hidden');
		el.classList.remove('success', 'error');
		el.classList.add(type);
		el.textContent = message;
	}

	function isStrong(pw) {
		return /[A-Z]/.test(pw) && /[a-z]/.test(pw) && /\d/.test(pw) && /[^A-Za-z0-9]/.test(pw) && pw.length >= 8;
	}

	function onForm(formId, buttonId, fields) {
		const form = byId(formId);
		if (!form) return;
		const button = byId(buttonId);
		const inputs = fields.map(id => byId(id)).filter(Boolean);
		function validate() {
			let ok = true;
			inputs.forEach(input => { if (!input.value || input.value.trim() === '') ok = false; });
			if (byId('email')) {
				const email = byId('email').value.trim();
				ok = ok && /.+@.+\..+/.test(email);
			}
			if (byId('password')) {
				// For login, just check password is not empty (not as strict as registration)
				ok = ok && byId('password').value.trim().length > 0;
			}
			if (byId('accept_policy')) {
				ok = ok && byId('accept_policy').checked;
			}
			button.disabled = !ok;
		}
		form.addEventListener('input', validate);
		form.addEventListener('change', validate);
		validate();
	}

	document.addEventListener('DOMContentLoaded', () => {
		console.log('Validation.js loaded and running');
		onForm('registerForm', 'registerButton', ['name', 'email', 'password']);
		onForm('loginForm', 'loginButton', ['identifier', 'password']);
		const pw = byId('password');
		if (pw) {
			pw.addEventListener('input', () => {
				// Don't show password strength warnings on login page
				// Just clear any existing notices
				byId('clientNotice').classList.add('hidden');
			});
		}

		// Password eye toggles
		document.querySelectorAll('[data-toggle="password"]').forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				const targetId = btn.getAttribute('data-target');
				if (!targetId) return;
				const input = byId(targetId);
				if (!input) return;
				if (input.type === 'password') {
					input.type = 'text';
					btn.textContent = 'Hide';
				} else {
					input.type = 'password';
					btn.textContent = 'Show';
				}
				input.focus();
			});
		});

		// Login form validation (no RFID required)
		const loginForm = document.getElementById('loginForm');
		const loginBtn = document.getElementById('loginButton');
		
		if (loginForm && loginBtn) {
			// Login form will submit normally without RFID
		}

		// Sidebar toggle and manual registration role modal
		const sidebarToggle = document.getElementById('sidebarToggle');
		const appSidebar = document.getElementById('appSidebar');
		const mainContent = document.getElementById('mainContent');
		
		if (sidebarToggle && appSidebar && mainContent) {
		// Function to update sidebar state and content layout
		window.updateSidebarState = function() {
			const isMobile = window.innerWidth < 768;
			const isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
			const isDesktop = window.innerWidth >= 1024;
			const isHidden = appSidebar.classList.contains('-translate-x-full');
			
			// Update data attribute for CSS
			appSidebar.setAttribute('data-sidebar-state', isHidden ? 'hidden' : 'visible');
			
			// Remove existing classes
			mainContent.classList.remove('sidebar-open');
			
			if (isMobile) {
				// On mobile, sidebar is overlay - content stays full width
				mainContent.style.marginLeft = '0';
				mainContent.style.paddingLeft = '0';
				mainContent.style.width = '100%';
			} else if (isTablet) {
				// On tablet, sidebar affects layout
				if (isHidden) {
					mainContent.style.marginLeft = '0';
					mainContent.style.paddingLeft = '0';
					mainContent.style.width = '100%';
				} else {
					mainContent.style.marginLeft = '0';
					mainContent.style.paddingLeft = '280px';
					mainContent.style.width = 'calc(100% - 280px)';
					mainContent.classList.add('sidebar-open');
				}
			} else if (isDesktop) {
				// On desktop, sidebar affects layout
				if (isHidden) {
					mainContent.style.marginLeft = '0';
					mainContent.style.paddingLeft = '0';
					mainContent.style.width = '100%';
				} else {
					mainContent.style.marginLeft = '0';
					mainContent.style.paddingLeft = '320px';
					mainContent.style.width = 'calc(100% - 320px)';
					mainContent.classList.add('sidebar-open');
				}
			}
		};
			
			// Initialize sidebar state
			function initSidebar() {
				const isMobile = window.innerWidth < 768;
				// Both mobile and desktop: hidden by default (collapsed)
				appSidebar.classList.add('-translate-x-full');
				window.updateSidebarState();
			}
			
			// Initialize on page load
			initSidebar();
			
			// Toggle sidebar
			sidebarToggle.addEventListener('click', () => {
				appSidebar.classList.toggle('-translate-x-full');
				window.updateSidebarState();
			});
			
			// Handle window resize
			window.addEventListener('resize', () => {
				initSidebar();
			});
		}
		
		// Close sidebar when clicking outside on mobile
		document.addEventListener('click', (e) => {
			if (window.innerWidth < 768 && appSidebar && !appSidebar.contains(e.target) && !sidebarToggle?.contains(e.target)) {
				appSidebar.classList.add('-translate-x-full');
			}
		});

		// Student form enhancements with comprehensive validation
		const consentChk = document.getElementById('consentedChk');
		const saveBtn = document.getElementById('saveStudentBtn');
		const continueText = document.getElementById('continueText');
		const formHelpText = document.getElementById('formHelpText');
		const consentLabel = document.getElementById('consentLabel');
		const consentHelpText = document.getElementById('consentHelpText');
		
		// Required fields for student form (year_grade is conditionally required)
		const requiredFields = ['name', 'level'];
		
		function validateForm() {
			let allFieldsFilled = true;
			let missingFields = [];
			
			// Check required fields
			requiredFields.forEach(fieldName => {
				const field = document.querySelector(`[name="${fieldName}"]`);
				if (field && (!field.value || field.value.trim() === '')) {
					allFieldsFilled = false;
					missingFields.push(fieldName.charAt(0).toUpperCase() + fieldName.slice(1));
				}
			});
			
			// Check year/grade field if it's visible and required
			const yearGradeField = document.getElementById('yearGradeField');
			const yearGradeInput = document.getElementById('yearGradeInput');
			if (yearGradeField && yearGradeInput && !yearGradeField.classList.contains('hidden')) {
				if (!yearGradeInput.value || yearGradeInput.value === '') {
					allFieldsFilled = false;
					const level = document.getElementById('levelSelect')?.value;
					if (level === 'College') {
						missingFields.push('Year');
					} else if (['Elementary', 'High School', 'Senior High School'].includes(level)) {
						missingFields.push('Grade');
					}
				}
			}
			
			// Check consent
			const consentGiven = consentChk.checked;
			
			// Update button state
			const canSubmit = allFieldsFilled && consentGiven;
			saveBtn.disabled = !canSubmit;
			
			if (canSubmit) {
				saveBtn.className = 'w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-xl transition';
				continueText.textContent = 'Continue';
				formHelpText.textContent = 'All requirements completed! You can now submit the form.';
			} else {
				saveBtn.className = 'w-full bg-slate-400 text-white font-semibold py-3 rounded-xl transition cursor-not-allowed';
				continueText.textContent = 'Complete all required fields and read the Data Privacy Consent';
				
				if (!allFieldsFilled) {
					formHelpText.textContent = `Fill in all required fields: ${missingFields.join(', ')}`;
				} else if (!consentGiven) {
					formHelpText.textContent = 'Read and agree to the Data Privacy Consent';
				}
			}
		}
		
		// Update consent checkbox state and help text
		function updateConsentState() {
			const isEnabled = consentChk.disabled === false;
			consentLabel.className = isEnabled ? 'text-slate-700' : 'text-slate-400';
			consentHelpText.textContent = isEnabled ? 
				'You can now check this box after reading the complete Data Privacy Consent' : 
				'Click "Show All" above to read the complete Data Privacy Consent';
		}
		
		if (consentChk && saveBtn) {
			// Monitor all form fields
			requiredFields.forEach(fieldName => {
				const field = document.querySelector(`[name="${fieldName}"]`);
				if (field) {
					field.addEventListener('input', validateForm);
					field.addEventListener('change', validateForm);
				}
			});
			
			// Monitor year/grade field if it exists
			const yearGradeInput = document.getElementById('yearGradeInput');
			if (yearGradeInput) {
				yearGradeInput.addEventListener('input', validateForm);
				yearGradeInput.addEventListener('change', validateForm);
			}
			
			// Monitor consent checkbox
			consentChk.addEventListener('change', validateForm);
			
			// Initial validation
			validateForm();
			updateConsentState();
		}

		// Privacy modal functionality
		const showPrivacyBtn = document.getElementById('showPrivacyBtn');
		const privacyModal = document.getElementById('privacyModal');
		const privacyCloseBtn = document.getElementById('privacyCloseBtn');
		const privacyContent = document.getElementById('privacyContent');
		const privacyReadChk = document.getElementById('privacyReadChk');
		const consentedChk = document.getElementById('consentedChk');
		
		if (showPrivacyBtn && privacyModal && privacyCloseBtn && privacyContent && privacyReadChk) {
			// Function to check if user has scrolled to bottom
			function checkScrollComplete() {
				const isAtBottom = Math.ceil(privacyContent.scrollTop + privacyContent.clientHeight) >= privacyContent.scrollHeight - 5;
				privacyReadChk.disabled = !isAtBottom;
				if (isAtBottom) {
					privacyReadChk.parentElement.classList.remove('text-slate-400');
					privacyReadChk.parentElement.classList.add('text-slate-700');
				} else {
					privacyReadChk.parentElement.classList.add('text-slate-400');
					privacyReadChk.parentElement.classList.remove('text-slate-700');
				}
			}
			
			// Function to sync modal checkbox with form checkbox
			function syncCheckboxes() {
				if (consentedChk) {
					consentedChk.disabled = !privacyReadChk.checked;
					consentedChk.checked = privacyReadChk.checked;
					// Update consent state and form validation
					updateConsentState();
					validateForm();
				}
			}
			
			showPrivacyBtn.addEventListener('click', () => {
				privacyModal.classList.remove('hidden');
				privacyModal.classList.add('flex');
				// Reset checkbox when opening
				privacyReadChk.checked = false;
				privacyReadChk.disabled = true;
				checkScrollComplete();
			});
			
			privacyCloseBtn.addEventListener('click', () => {
				privacyModal.classList.add('hidden');
				privacyModal.classList.remove('flex');
			});
			
			// Close on background click
			privacyModal.addEventListener('click', (e) => {
				if (e.target === privacyModal) {
					privacyModal.classList.add('hidden');
					privacyModal.classList.remove('flex');
				}
			});
			
			// Monitor scroll to enable checkbox
			privacyContent.addEventListener('scroll', checkScrollComplete);
			
			// Sync checkboxes when modal checkbox changes
			privacyReadChk.addEventListener('change', syncCheckboxes);
		}


		// RFID portal: auto-submit on input focus/scan and role modal
		const rfidSearchForm = document.getElementById('rfidSearchForm');
		const rfidSearchInput = document.getElementById('rfidSearchInput');
		if (rfidSearchForm && rfidSearchInput) {
			// Ensure autofocus without click
			rfidSearchInput.focus();
			rfidSearchInput.addEventListener('input', () => {
				if (rfidSearchInput.value.trim().length >= 4) {
					rfidSearchForm.submit();
				}
			});
		}

		// Disable RFID auto-submit on forms (student/faculty forms and account settings)
		const formRfidInputs = document.querySelectorAll('input[name="rfid"], input[name="new_rfid"]:not(#rfidSearchInput)');
		formRfidInputs.forEach(input => {
			input.addEventListener('keydown', (e) => {
				if (e.key === 'Enter') {
					e.preventDefault();
				}
			});
		});


		// Student form: Dynamic fields based on level selection
		const levelSelect = document.getElementById('levelSelect');
		const courseField = document.getElementById('courseField');
		const sectionField = document.getElementById('sectionField');
		const strandField = document.getElementById('strandField');
		const yearGradeField = document.getElementById('yearGradeField');
		const yearGradeLabel = document.getElementById('yearGradeLabel');
		const yearGradeInput = document.getElementById('yearGradeInput');
		
		if (levelSelect && courseField && sectionField && strandField && yearGradeField && yearGradeLabel && yearGradeInput) {
			function updateFields() {
				const level = levelSelect.value;
				
				// Hide all fields first
				courseField.classList.add('hidden');
				sectionField.classList.add('hidden');
				strandField.classList.add('hidden');
				yearGradeField.classList.add('hidden');
				
				// Clear and populate grade/year dropdown
				yearGradeInput.innerHTML = '<option value="">Select Grade/Year</option>';
				
				// Reset label text
				yearGradeLabel.textContent = 'Year/Grade';
				
				// Show appropriate fields based on level
				if (level === 'Pre-school') {
					// Pre-school: Section only
					sectionField.classList.remove('hidden');
				} else if (level === 'Elementary') {
					// Elementary: Grade and Section
					yearGradeField.classList.remove('hidden');
					yearGradeLabel.textContent = 'Grade';
					sectionField.classList.remove('hidden');
					
					// Add Elementary grade options (1-6)
					for (let i = 1; i <= 6; i++) {
						const option = document.createElement('option');
						option.value = `Grade ${i}`;
						option.textContent = `Grade ${i}`;
						yearGradeInput.appendChild(option);
					}
				} else if (level === 'High School') {
					// High School: Grade and Section
					yearGradeField.classList.remove('hidden');
					yearGradeLabel.textContent = 'Grade';
					sectionField.classList.remove('hidden');
					
					// Add High School grade options (7-10)
					for (let i = 7; i <= 10; i++) {
						const option = document.createElement('option');
						option.value = `Grade ${i}`;
						option.textContent = `Grade ${i}`;
						yearGradeInput.appendChild(option);
					}
				} else if (level === 'Senior High School') {
					// Senior High School: Grade and Strand (no section)
					yearGradeField.classList.remove('hidden');
					yearGradeLabel.textContent = 'Grade';
					strandField.classList.remove('hidden');
					
					// Add Senior High School grade options (11-12)
					for (let i = 11; i <= 12; i++) {
						const option = document.createElement('option');
						option.value = `Grade ${i}`;
						option.textContent = `Grade ${i}`;
						yearGradeInput.appendChild(option);
					}
				} else if (level === 'College') {
					// College: Year and Course
					yearGradeField.classList.remove('hidden');
					yearGradeLabel.textContent = 'Year';
					courseField.classList.remove('hidden');
					
					// Add College year options (1-5 for special cases)
					for (let i = 1; i <= 5; i++) {
						const option = document.createElement('option');
						let yearText;
						if (i === 1) yearText = '1st Year';
						else if (i === 2) yearText = '2nd Year';
						else if (i === 3) yearText = '3rd Year';
						else if (i === 4) yearText = '4th Year';
						else if (i === 5) yearText = '5th Year (Irregular)';
						
						option.value = yearText;
						option.textContent = yearText;
						yearGradeInput.appendChild(option);
					}
				}
			}
			
			levelSelect.addEventListener('change', () => {
				updateFields();
				// Trigger validation after fields are updated
				if (typeof validateForm === 'function') {
					validateForm();
				}
			});
			
			// Initialize on page load and set existing values
			updateFields();
			
			// Set existing year/grade value if editing
			const existingYearGrade = yearGradeInput.getAttribute('data-existing-value');
			if (existingYearGrade) {
				// Wait a bit for the options to be populated
				setTimeout(() => {
					yearGradeInput.value = existingYearGrade;
				}, 100);
			}
		}

		// Faculty form: Senior tag for age 60+
		const ageInput = document.getElementById('ageInput');
		const srTag = document.getElementById('srTag');
		
		if (ageInput && srTag) {
			function updateSrTag() {
				const age = parseInt(ageInput.value);
				if (age >= 60) {
					srTag.classList.remove('hidden');
				} else {
					srTag.classList.add('hidden');
				}
			}
			
			ageInput.addEventListener('input', updateSrTag);
			updateSrTag(); // Initialize on page load
		}

		const roleBtn = document.getElementById('roleChooseBtn');
		const roleModal = document.getElementById('roleModal');
		const roleStudent = document.getElementById('roleStudent');
		const roleFaculty = document.getElementById('roleFaculty');
		const roleCancel = document.getElementById('roleCancel');
		if (roleBtn && roleModal && roleStudent && roleFaculty && roleCancel) {
			roleBtn.addEventListener('click', () => {
				roleModal.classList.remove('hidden');
				roleModal.classList.add('flex');
			});
			roleCancel.addEventListener('click', () => {
				roleModal.classList.add('hidden');
				roleModal.classList.remove('flex');
			});
			roleStudent.addEventListener('click', (e) => {
				e.preventDefault();
				const q = new URLSearchParams(window.location.search);
				const rfid = (document.getElementById('rfidSearchInput')?.value || '');
				window.location.href = 'student_form.php?rfid=' + encodeURIComponent(rfid);
			});
			roleFaculty.addEventListener('click', (e) => {
				e.preventDefault();
				const rfid = (document.getElementById('rfidSearchInput')?.value || '');
				window.location.href = 'faculty_form.php?rfid=' + encodeURIComponent(rfid);
			});
		}
	});
})();

// Auto-formatting code outside IIFE to ensure it runs
document.addEventListener('DOMContentLoaded', () => {
	console.log('Auto-formatting loaded');
	
	// Auto-format date of birth fields (DD/MM/YYYY)
	const dobInputs = document.querySelectorAll('input[name="dob"]');
	console.log('Found DOB inputs:', dobInputs.length);
	dobInputs.forEach(input => {
		let lastValue = '';
		
		input.addEventListener('input', (e) => {
			let value = e.target.value;
			const cursorPos = e.target.selectionStart;
			
			// Only format if user is typing at the end or the value is getting longer
			if (value.length > lastValue.length && cursorPos >= value.length - 1) {
				// Remove all non-digits
				let digitsOnly = value.replace(/\D/g, '');
				
				// Add slashes at appropriate positions
				if (digitsOnly.length >= 2) {
					digitsOnly = digitsOnly.substring(0, 2) + '/' + digitsOnly.substring(2);
				}
				if (digitsOnly.length >= 5) {
					digitsOnly = digitsOnly.substring(0, 5) + '/' + digitsOnly.substring(5, 9);
				}
				
				// Only update if the formatted value is different
				if (digitsOnly !== value) {
					e.target.value = digitsOnly;
					// Set cursor position after the last typed character
					const newCursorPos = Math.min(cursorPos + (digitsOnly.length - value.length), digitsOnly.length);
					e.target.setSelectionRange(newCursorPos, newCursorPos);
				}
			}
			
			lastValue = e.target.value;
		});
		
		// Prevent typing more than 10 characters (DD/MM/YYYY)
		input.addEventListener('keydown', (e) => {
			if (e.target.value.length >= 10 && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
				e.preventDefault();
			}
		});
	});

	// Address field auto-layout enhancement
	const addressInputs = document.querySelectorAll('input[name="address"]');
	addressInputs.forEach(input => {
		// Auto-resize based on content
		input.addEventListener('input', function() {
			// Get current cursor position
			const cursorPos = this.selectionStart;
			let value = this.value;
			
			// Only apply formatting if the value has changed significantly
			// Don't interfere with normal typing
			if (value.length > 0) {
				// Auto-capitalize first letter of the entire string only
				if (value.charAt(0) !== value.charAt(0).toUpperCase()) {
					value = value.charAt(0).toUpperCase() + value.slice(1);
				}
			}
			
			// Update the value if it changed
			if (value !== this.value) {
				this.value = value;
				// Restore cursor position
				this.setSelectionRange(cursorPos, cursorPos);
			}
			
			// Auto-adjust height based on content length
			if (value.length > 50) {
				this.style.minHeight = '60px';
				this.style.lineHeight = '1.4';
			} else {
				this.style.minHeight = '48px';
				this.style.lineHeight = '1.5';
			}
		});
		
		// Format on blur to ensure proper capitalization
		input.addEventListener('blur', function() {
			let value = this.value.trim();
			if (value) {
				// Capitalize first letter of each word, preserving spaces
				value = value.replace(/\b\w/g, function(letter) {
					return letter.toUpperCase();
				});
				this.value = value;
			}
		});
	});

	// Auto-calculate age from date of birth
	const ageInputs = document.querySelectorAll('input[name="age"]');
	
	dobInputs.forEach((dobInput, index) => {
		const ageInput = ageInputs[index];
		
		if (ageInput) {
			function calculateAgeFromDOB(dobValue) {
				// Check if date format is complete (DD/MM/YYYY)
				if (dobValue && dobValue.length === 10 && dobValue.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
					const [day, month, year] = dobValue.split('/').map(Number);
					
					// Validate date
					const date = new Date(year, month - 1, day);
					const currentDate = new Date();
					
					// Check if date is valid and not in the future
					if (date.getDate() === day && date.getMonth() === month - 1 && date.getFullYear() === year && date <= currentDate) {
						// Calculate age
						let age = currentDate.getFullYear() - year;
						const monthDiff = currentDate.getMonth() - (month - 1);
						
						// Adjust age if birthday hasn't occurred this year
						if (monthDiff < 0 || (monthDiff === 0 && currentDate.getDate() < day)) {
							age--;
						}
						
						// Validate age range (0-120 years)
						if (age >= 0 && age <= 120) {
							ageInput.value = age;
							
							// Show Sr. tag for faculty if age >= 60
							const srTag = document.getElementById('srTag');
							if (srTag && age >= 60) {
								srTag.classList.remove('hidden');
							} else if (srTag) {
								srTag.classList.add('hidden');
							}
							
							// Clear any error styling
							dobInput.classList.remove('border-red-500');
							dobInput.classList.add('border-slate-300');
							return true;
						} else {
							ageInput.value = '';
							dobInput.classList.add('border-red-500');
							dobInput.classList.remove('border-slate-300');
							return false;
						}
					} else {
						ageInput.value = '';
						dobInput.classList.add('border-red-500');
						dobInput.classList.remove('border-slate-300');
						return false;
					}
				} else {
					ageInput.value = '';
					dobInput.classList.remove('border-red-500');
					dobInput.classList.add('border-slate-300');
					return false;
				}
			}
			
			// Add event listeners
			dobInput.addEventListener('input', (e) => {
				calculateAgeFromDOB(e.target.value.trim());
			});
			
			dobInput.addEventListener('blur', (e) => {
				calculateAgeFromDOB(e.target.value.trim());
			});
			
			// Calculate age on page load if DOB is already filled
			if (dobInput.value.trim().length === 10) {
				calculateAgeFromDOB(dobInput.value.trim());
			}
		}
	});

	// Auto-format allergies field - add N/A if empty
	const allergiesInputs = document.querySelectorAll('input[name="allergies"], textarea[name="allergies"]');
	allergiesInputs.forEach(input => {
		input.addEventListener('blur', (e) => {
			// If field is empty or only whitespace, set to N/A
			if (!e.target.value.trim()) {
				e.target.value = 'N/A';
			}
		});
		
		// Also handle on form submit
		const form = input.closest('form');
		if (form) {
			form.addEventListener('submit', (e) => {
				if (!input.value.trim()) {
					input.value = 'N/A';
				}
			});
		}
	});
});

// Global keyboard shortcuts handler
document.addEventListener('keydown', (e) => {
	// Tab key to toggle sidebar/menu - only when not typing in input fields
	if (e.key === 'Tab' && !e.ctrlKey && !e.altKey && !e.shiftKey) {
		// Check if we're in an input field
		const activeElement = document.activeElement;
		const isInputField = activeElement && (
			activeElement.tagName === 'INPUT' || 
			activeElement.tagName === 'TEXTAREA' || 
			activeElement.contentEditable === 'true'
		);
		
		if (isInputField) {
			return; // Don't interfere with typing
		}
		
		console.log('Tab key pressed - attempting to toggle sidebar');
		const sidebarToggle = document.getElementById('sidebarToggle');
		const appSidebar = document.getElementById('appSidebar');
		
		console.log('Sidebar elements found:', { sidebarToggle: !!sidebarToggle, appSidebar: !!appSidebar });
		
		if (sidebarToggle && appSidebar) {
			e.preventDefault();
			console.log('Toggling sidebar...');
			
			// Check current state before toggle
			const isHidden = appSidebar.classList.contains('-translate-x-full');
			console.log('Sidebar current state - isHidden:', isHidden);
			
			// Toggle the sidebar
			appSidebar.classList.toggle('-translate-x-full');
			
			// Check state after toggle
			const isHiddenAfter = appSidebar.classList.contains('-translate-x-full');
			console.log('Sidebar after toggle - isHidden:', isHiddenAfter);
			
			// Update sidebar state if the function exists
			if (typeof window.updateSidebarState === 'function') {
				console.log('Calling updateSidebarState...');
				window.updateSidebarState();
			} else {
				console.log('updateSidebarState function not found');
				// Fallback: manually update the layout
				const mainContent = document.getElementById('mainContent');
				if (mainContent) {
					const isHidden = appSidebar.classList.contains('-translate-x-full');
					const isMobile = window.innerWidth < 768;
					
					if (isMobile) {
						mainContent.style.marginLeft = '0';
						mainContent.style.paddingLeft = '0';
					} else {
						if (isHidden) {
							mainContent.style.marginLeft = '0';
							mainContent.style.paddingLeft = '0';
						} else {
							mainContent.style.marginLeft = '0';
							mainContent.style.paddingLeft = '18rem';
						}
					}
					console.log('Applied fallback layout update');
				}
			}
		} else {
			console.log('Sidebar elements not found on this page');
		}
		return;
	}
	
	// Ctrl+D for dashboard
	if (e.key === 'd' && e.ctrlKey && !e.altKey && !e.shiftKey) {
		e.preventDefault();
		// Check if we're in an input field
		const activeElement = document.activeElement;
		const isInputField = activeElement && (
			activeElement.tagName === 'INPUT' || 
			activeElement.tagName === 'TEXTAREA' || 
			activeElement.contentEditable === 'true'
		);
		
		if (isInputField) {
			return; // Don't interfere with typing
		}
		
		console.log('Ctrl+D pressed - navigating to dashboard');
		window.location.href = 'dashboard.php';
		return;
	}
	
	// Ctrl+K for RFID portal search
	if (e.key === 'k' && e.ctrlKey && !e.altKey && !e.shiftKey) {
		e.preventDefault();
		// Check if we're in an input field
		const activeElement = document.activeElement;
		const isInputField = activeElement && (
			activeElement.tagName === 'INPUT' || 
			activeElement.tagName === 'TEXTAREA' || 
			activeElement.contentEditable === 'true'
		);
		
		if (isInputField) {
			return; // Don't interfere with typing
		}
		
		console.log('Ctrl+K pressed - navigating to RFID portal');
		window.location.href = 'rfid_portal.php';
		return;
	}
	
	// Ctrl+S for save form
	if (e.key === 's' && e.ctrlKey && !e.altKey && !e.shiftKey) {
		e.preventDefault();
		// Check if we're in an input field
		const activeElement = document.activeElement;
		const isInputField = activeElement && (
			activeElement.tagName === 'INPUT' || 
			activeElement.tagName === 'TEXTAREA' || 
			activeElement.contentEditable === 'true'
		);
		
		if (isInputField) {
			return; // Don't interfere with typing
		}
		
		console.log('Ctrl+S pressed - attempting to save form');
		
		// Look for common save buttons
		const saveButtons = [
			'saveButton',
			'saveVisitationButton', 
			'saveMedicalButton',
			'saveFormButton',
			'registerButton',
			'loginButton',
			'updateButton',
			'submitButton'
		];
		
		for (const buttonId of saveButtons) {
			const button = document.getElementById(buttonId);
			if (button && !button.disabled && !button.classList.contains('hidden')) {
				console.log('Found save button:', buttonId);
				button.click();
				return;
			}
		}
		
		// Fallback: look for any button with "save" in the text
		const allButtons = document.querySelectorAll('button');
		for (const button of allButtons) {
			if (button.textContent.toLowerCase().includes('save') && 
				!button.disabled && 
				!button.classList.contains('hidden')) {
				console.log('Found save button by text:', button.textContent);
				button.click();
				return;
			}
		}
		
		console.log('No save button found');
		return;
	}
	
	// Ctrl+R for refresh page
	if (e.key === 'r' && e.ctrlKey && !e.altKey && !e.shiftKey) {
		e.preventDefault();
		// Check if we're in an input field
		const activeElement = document.activeElement;
		const isInputField = activeElement && (
			activeElement.tagName === 'INPUT' || 
			activeElement.tagName === 'TEXTAREA' || 
			activeElement.contentEditable === 'true'
		);
		
		if (isInputField) {
			return; // Don't interfere with typing
		}
		
		console.log('Ctrl+R pressed - refreshing page');
		window.location.reload();
		return;
	}
	
	// Escape key for modals and back navigation
	if (e.key === 'Escape') {
		// Check for open modals and close them
		const modals = [
			'visitationModal',
			'medicalHistoryModal', 
			'medicalFormFullScreen',
			'editPatientModal',
			'editModal',
			'privacyModal',
			'medicalFormModal'
		];
		
		for (const modalId of modals) {
			const modal = document.getElementById(modalId);
			if (modal && !modal.classList.contains('hidden')) {
				// Close the modal based on its type
				switch(modalId) {
					case 'visitationModal':
						if (typeof closeVisitationModal === 'function') {
							closeVisitationModal();
						}
						break;
					case 'medicalHistoryModal':
						if (typeof closeMedicalHistoryModal === 'function') {
							closeMedicalHistoryModal();
						}
						break;
					case 'medicalFormFullScreen':
						if (typeof closeMedicalFormFullScreen === 'function') {
							closeMedicalFormFullScreen();
						}
						break;
					case 'editPatientModal':
						if (typeof closeEditPatientModal === 'function') {
							closeEditPatientModal();
						}
						break;
					case 'editModal':
						if (typeof closeEditModal === 'function') {
							closeEditModal();
						}
						break;
					case 'privacyModal':
						if (typeof closePrivacyModal === 'function') {
							closePrivacyModal();
						}
						break;
					case 'medicalFormModal':
						if (typeof closeMedicalFormModal === 'function') {
							closeMedicalFormModal();
						}
						break;
				}
				e.preventDefault();
				return;
			}
		}
		
		// If no modals are open, check if we can go back
		if (window.history.length > 1) {
			// Check if we're not on a critical page that shouldn't allow back navigation
			const currentPath = window.location.pathname;
			const criticalPages = ['/login.php', '/register.php', '/dashboard.php'];
			
			if (!criticalPages.includes(currentPath)) {
				window.history.back();
				e.preventDefault();
			}
		}
	}
});

// Enhanced modal close functions with Escape key support
function closeModal(modalId) {
	const modal = document.getElementById(modalId);
	if (modal) {
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	}
}

// Add Escape key support to existing modal functions
function addEscapeKeySupport() {
	// Override existing modal functions to ensure they work with Escape key
	const originalCloseVisitationModal = window.closeVisitationModal;
	const originalCloseMedicalHistoryModal = window.closeMedicalHistoryModal;
	const originalCloseMedicalFormFullScreen = window.closeMedicalFormFullScreen;
	const originalCloseEditPatientModal = window.closeEditPatientModal;
	const originalCloseEditModal = window.closeEditModal;
	const originalClosePrivacyModal = window.closePrivacyModal;
	const originalCloseMedicalFormModal = window.closeMedicalFormModal;
	
	if (originalCloseVisitationModal) {
		window.closeVisitationModal = function() {
			originalCloseVisitationModal();
			// Additional cleanup if needed
		};
	}
	
	if (originalCloseMedicalHistoryModal) {
		window.closeMedicalHistoryModal = function() {
			originalCloseMedicalHistoryModal();
			// Additional cleanup if needed
		};
	}
	
	if (originalCloseMedicalFormFullScreen) {
		window.closeMedicalFormFullScreen = function() {
			originalCloseMedicalFormFullScreen();
			// Additional cleanup if needed
		};
	}
	
	if (originalCloseEditPatientModal) {
		window.closeEditPatientModal = function() {
			originalCloseEditPatientModal();
			// Additional cleanup if needed
		};
	}
	
	if (originalCloseEditModal) {
		window.closeEditModal = function() {
			originalCloseEditModal();
			// Additional cleanup if needed
		};
	}
	
	if (originalClosePrivacyModal) {
		window.closePrivacyModal = function() {
			originalClosePrivacyModal();
			// Additional cleanup if needed
		};
	}
	
	if (originalCloseMedicalFormModal) {
		window.closeMedicalFormModal = function() {
			originalCloseMedicalFormModal();
			// Additional cleanup if needed
		};
	}
}

// Initialize Escape key support when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
	addEscapeKeySupport();
});



