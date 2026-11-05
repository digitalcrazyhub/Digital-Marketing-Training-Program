/**
 * Digital Crazy Hub - AI Integrated Digital Marketing Training Program
 * Main JavaScript Application
 * Rebuilt & Enhanced for Performance, Accessibility, and Mobile UX
 */

'use strict';

// 1. Mark JavaScript availability
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {

	/* ==========================================================================
	   Scroll Reveal Animations
	   ========================================================================== */
	const revealElements = document.querySelectorAll('.reveal, .reveal-group');

	if ('IntersectionObserver' in window) {
		const revealObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					entry.target.classList.add('visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.1, rootMargin: '0px 0px -20px 0px' });

		revealElements.forEach(el => revealObserver.observe(el));
	} else {
		revealElements.forEach(el => el.classList.add('visible'));
	}

	/* ==========================================================================
	   Lead Enrollment Form: Validation, Reactive Button & Submission
	   ========================================================================== */
	const enrollForm = document.getElementById('enrollForm');
	const submitBtn = document.getElementById('submitBtn');
	const formSuccessBanner = document.getElementById('formSuccessBanner');
	const resetFormBtn = document.getElementById('resetFormBtn');

	if (enrollForm && submitBtn) {
		const nameInput = document.getElementById('name');
		const emailInput = document.getElementById('email');
		const phoneInput = document.getElementById('phone');
		const profileSelect = document.getElementById('profile');

		const inputs = [nameInput, emailInput, phoneInput, profileSelect].filter(Boolean);

		function isValidEmail(val) {
			return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim());
		}

		function isValidPhone(val) {
			const clean = val.replace(/[\s\-\(\)\+]/g, '');
			return clean.length >= 10 && clean.length <= 15 && /^\d+$/.test(clean);
		}

		function validateField(field, showError = false) {
			const formGroup = field.closest('.form-group');
			let isValid = false;

			if (field === nameInput) {
				isValid = field.value.trim().length >= 2;
			} else if (field === emailInput) {
				isValid = isValidEmail(field.value);
			} else if (field === phoneInput) {
				isValid = isValidPhone(field.value);
			} else if (field === profileSelect) {
				isValid = field.value.trim().length > 0;
			}

			if (formGroup && showError) {
				if (!isValid) {
					formGroup.classList.add('has-error');
				} else {
					formGroup.classList.remove('has-error');
				}
			} else if (formGroup && isValid) {
				formGroup.classList.remove('has-error');
			}

			return isValid;
		}

		function checkFormValidity() {
			const isNameValid = nameInput ? nameInput.value.trim().length >= 2 : false;
			const isEmailValid = emailInput ? isValidEmail(emailInput.value) : false;
			const isPhoneValid = phoneInput ? isValidPhone(phoneInput.value) : false;
			const isProfileValid = profileSelect ? profileSelect.value.trim().length > 0 : false;

			const formValid = isNameValid && isEmailValid && isPhoneValid && isProfileValid;
			// submitBtn.disabled = !formValid;
			return formValid;
		}

		inputs.forEach(input => {
			input.addEventListener('input', () => {
				validateField(input, false);
				checkFormValidity();
			});

			input.addEventListener('blur', () => {
				if (input.value.trim().length > 0) {
					validateField(input, true);
				}
				checkFormValidity();
			});
		});

		// Initial check
		checkFormValidity();

		// Handle Form Submission
		enrollForm.addEventListener('submit', (e) => {
			e.preventDefault();

			let allValid = true;
			inputs.forEach(input => {
				const valid = validateField(input, true);
				if (!valid) allValid = false;
			});

			if (!allValid) {
				const firstError = enrollForm.querySelector('.has-error input, .has-error select');
				if (firstError) firstError.focus();
				return;
			}

			// Simulating submission feedback
			submitBtn.disabled = true;
			submitBtn.classList.add('is-submitting');
			const originalText = submitBtn.querySelector('.btn-text').textContent;
			submitBtn.querySelector('.btn-text').textContent = 'Securing Your Seat...';

			setTimeout(() => {
				submitBtn.classList.remove('is-submitting');
				submitBtn.querySelector('.btn-text').textContent = originalText;

				const studentName = nameInput.value.trim();
				enrollForm.style.display = 'none';

				if (formSuccessBanner) {
					const successNameSpan = document.getElementById('successStudentName');
					if (successNameSpan) successNameSpan.textContent = studentName || 'Learner';
					formSuccessBanner.classList.add('visible');
				}
			}, 700);
		});

		if (resetFormBtn && formSuccessBanner) {
			resetFormBtn.addEventListener('click', () => {
				enrollForm.reset();
				inputs.forEach(input => {
					const fg = input.closest('.form-group');
					if (fg) fg.classList.remove('has-error');
				});
				formSuccessBanner.classList.remove('visible');
				enrollForm.style.display = 'flex';
				checkFormValidity();
			});
		}
	}

	/* ==========================================================================
	   Curriculum Accordion (Modules & Subitems)
	   ========================================================================== */
	const curriculum = document.getElementById('curriculum');

	if (curriculum) {
		const modules = Array.from(curriculum.querySelectorAll('[data-module]'));
		const toggleAllBtn = document.getElementById('toggleAllBtn');
		const searchInput = document.getElementById('curriculumSearch');
		let allExpanded = false;

		function setExpanded(toggle, panel, expanded) {
			if (!toggle || !panel) return;
			toggle.setAttribute('aria-expanded', String(expanded));
			panel.setAttribute('aria-hidden', String(!expanded));
		}

		// Configure Module Toggles
		modules.forEach((module, moduleIndex) => {
			const toggle = module.querySelector(':scope > [data-module-toggle]');
			const panel = module.querySelector(':scope > .module-body');

			if (!toggle || !panel) return;

			toggle.type = 'button';
			panel.id = `curriculum-module-${moduleIndex + 1}`;
			toggle.setAttribute('aria-controls', panel.id);

			// Module 1 starts open by default
			const initiallyOpen = moduleIndex === 0;
			module.classList.toggle('open', initiallyOpen);
			setExpanded(toggle, panel, initiallyOpen);

			toggle.addEventListener('click', () => {
				const isCurrentlyOpen = module.classList.contains('open');
				module.classList.toggle('open', !isCurrentlyOpen);
				setExpanded(toggle, panel, !isCurrentlyOpen);
			});
		});

		// Configure Subitem Toggles
		curriculum.querySelectorAll('[data-subgroup]').forEach((group, groupIndex) => {
			const subitems = Array.from(group.querySelectorAll('[data-subitem]'));

			subitems.forEach((subitem, subitemIndex) => {
				const toggle = subitem.querySelector(':scope > [data-subitem-toggle]');
				const panel = subitem.querySelector(':scope > .subitem-body');

				if (!toggle || !panel) return;

				toggle.type = 'button';
				panel.id = `curriculum-subitem-${groupIndex + 1}-${subitemIndex + 1}`;
				toggle.setAttribute('aria-controls', panel.id);

				// Subitem 1 starts open
				const initiallyOpen = subitemIndex === 0;
				subitem.classList.toggle('open', initiallyOpen);
				setExpanded(toggle, panel, initiallyOpen);

				toggle.addEventListener('click', () => {
					const isCurrentlyOpen = subitem.classList.contains('open');
					subitem.classList.toggle('open', !isCurrentlyOpen);
					setExpanded(toggle, panel, !isCurrentlyOpen);
				});
			});
		});

		// "Expand All / Collapse All" Toolbar Action
		if (toggleAllBtn) {
			toggleAllBtn.addEventListener('click', () => {
				allExpanded = !allExpanded;

				modules.forEach(module => {
					const toggle = module.querySelector(':scope > [data-module-toggle]');
					const panel = module.querySelector(':scope > .module-body');
					module.classList.toggle('open', allExpanded);
					setExpanded(toggle, panel, allExpanded);

					module.querySelectorAll('[data-subitem]').forEach(subitem => {
						const subToggle = subitem.querySelector(':scope > [data-subitem-toggle]');
						const subPanel = subitem.querySelector(':scope > .subitem-body');
						subitem.classList.toggle('open', allExpanded);
						setExpanded(subToggle, subPanel, allExpanded);
					});
				});

				const btnText = toggleAllBtn.querySelector('.btn-text');
				if (btnText) {
					btnText.textContent = allExpanded ? 'Collapse All' : 'Expand All';
				}
			});
		}

		// Curriculum Live Search / Filter
		if (searchInput) {
			searchInput.addEventListener('input', (e) => {
				const query = e.target.value.toLowerCase().trim();

				modules.forEach(module => {
					const moduleToggle = module.querySelector(':scope > [data-module-toggle]');
					const modulePanel = module.querySelector(':scope > .module-body');
					const subitems = Array.from(module.querySelectorAll('[data-subitem]'));

					if (!query) {
						// Reset visibility
						module.style.display = '';
						subitems.forEach(sub => sub.style.display = '');
						return;
					}

					let moduleHasMatch = false;
					const moduleHeaderTitle = moduleToggle ? moduleToggle.textContent.toLowerCase() : '';

					if (moduleHeaderTitle.includes(query)) {
						moduleHasMatch = true;
						subitems.forEach(sub => sub.style.display = '');
					} else {
						subitems.forEach(sub => {
							const text = sub.textContent.toLowerCase();
							if (text.includes(query)) {
								sub.style.display = '';
								moduleHasMatch = true;
								sub.classList.add('open');
								const sToggle = sub.querySelector('[data-subitem-toggle]');
								const sPanel = sub.querySelector('.subitem-body');
								setExpanded(sToggle, sPanel, true);
							} else {
								sub.style.display = 'none';
							}
						});
					}

					if (moduleHasMatch) {
						module.style.display = '';
						module.classList.add('open');
						setExpanded(moduleToggle, modulePanel, true);
					} else {
						module.style.display = 'none';
					}
				});
			});
		}
	}

	/* ==========================================================================
	   FAQ Accordion
	   ========================================================================== */
	const faqItems = document.querySelectorAll('.faq-item');
	faqItems.forEach((faqItem, idx) => {
		const questionBtn = faqItem.querySelector('.faq-question');
		const answerPanel = faqItem.querySelector('.faq-answer');

		if (!questionBtn || !answerPanel) return;

		questionBtn.type = 'button';
		answerPanel.id = `faq-answer-${idx + 1}`;
		questionBtn.setAttribute('aria-controls', answerPanel.id);
		questionBtn.setAttribute('aria-expanded', 'false');
		answerPanel.setAttribute('aria-hidden', 'true');

		questionBtn.addEventListener('click', () => {
			const isOpen = faqItem.classList.contains('open');

			// Close others for clean FAQ behavior
			faqItems.forEach(other => {
				if (other !== faqItem) {
					other.classList.remove('open');
					const ob = other.querySelector('.faq-question');
					const op = other.querySelector('.faq-answer');
					if (ob) ob.setAttribute('aria-expanded', 'false');
					if (op) op.setAttribute('aria-hidden', 'true');
				}
			});

			faqItem.classList.toggle('open', !isOpen);
			questionBtn.setAttribute('aria-expanded', String(!isOpen));
			answerPanel.setAttribute('aria-hidden', String(isOpen));
		});
	});

	/* ==========================================================================
	   Mobile Sticky Action Bar visibility handling
	   ========================================================================== */
	const mobileStickyBar = document.querySelector('.mobile-sticky-bar');
	const enrollmentSection = document.getElementById('enrollment');

	if (mobileStickyBar && enrollmentSection) {
		const formObserver = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				// Hide the sticky button when the user is already looking at the form
				if (entry.isIntersecting) {
					mobileStickyBar.style.transform = 'translateY(100%)';
					mobileStickyBar.style.transition = 'transform 0.25s ease';
				} else {
					mobileStickyBar.style.transform = 'translateY(0)';
				}
			});
		}, { threshold: 0.15 });

		formObserver.observe(enrollmentSection);
	}

	/* ==========================================================================
	   YouTube Video Facade: Clean Lazy Loading (Zero Console Violations on Load)
	   ========================================================================== */
	const videoCards = document.querySelectorAll('.video-card[data-video-id]');
	videoCards.forEach(card => {
		const videoId = card.getAttribute('data-video-id');
		const videoTitle = card.getAttribute('data-video-title') || 'Student Story';
		const frameWrap = card.querySelector('.frame-wrap');

		if (!videoId || !frameWrap) return;

		function loadIframe() {
			if (frameWrap.querySelector('iframe')) return;

			const iframe = document.createElement('iframe');
			iframe.src = `https://www.youtube.com/embed/${encodeURIComponent(videoId)}?autoplay=1&rel=0`;
			iframe.title = videoTitle;
			iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; compute-pressure');
			iframe.setAttribute('allowfullscreen', '');

			frameWrap.innerHTML = '';
			frameWrap.appendChild(iframe);
		}

		frameWrap.addEventListener('click', loadIframe);
		frameWrap.addEventListener('keydown', (e) => {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				loadIframe();
			}
		});
	});

	/* ==========================================================================
	   Student Training Gallery & Lightbox Popup Preview Modal
	   ========================================================================== */
	const lightboxModal = document.getElementById('imageLightbox');
	const lightboxImg = document.getElementById('lightboxImg');
	const lightboxBadge = document.getElementById('lightboxBadge');
	const lightboxCaption = document.getElementById('lightboxCaption');
	const lightboxCounter = document.getElementById('lightboxCounter');
	const lightboxClose = document.getElementById('lightboxClose');
	const lightboxBackdrop = document.getElementById('lightboxBackdrop');
	const lightboxPrev = document.getElementById('lightboxPrev');
	const lightboxNext = document.getElementById('lightboxNext');

	const galleryCards = Array.from(document.querySelectorAll('.gallery-card'));
	const galleryItems = galleryCards.map(card => ({
		src: card.getAttribute('data-src'),
		title: card.getAttribute('data-title'),
		badge: card.getAttribute('data-badge'),
		category: card.getAttribute('data-category')
	}));

	let currentLightboxIndex = 0;

	function updateLightboxContent(index) {
		if (!galleryItems[index]) return;
		currentLightboxIndex = index;
		const item = galleryItems[index];

		lightboxImg.src = item.src;
		lightboxImg.alt = item.title;
		if (lightboxBadge) lightboxBadge.textContent = item.badge;
		if (lightboxCaption) lightboxCaption.textContent = item.title;
		if (lightboxCounter) lightboxCounter.textContent = `${index + 1} / ${galleryItems.length}`;
	}

	function openLightbox(index) {
		if (!lightboxModal || !galleryItems.length) return;
		updateLightboxContent(index);
		lightboxModal.removeAttribute('hidden');
		document.body.style.overflow = 'hidden';
	}

	function closeLightbox() {
		if (!lightboxModal) return;
		lightboxModal.setAttribute('hidden', '');
		document.body.style.overflow = '';
	}

	function nextLightbox() {
		const nextIndex = (currentLightboxIndex + 1) % galleryItems.length;
		updateLightboxContent(nextIndex);
	}

	function prevLightbox() {
		const prevIndex = (currentLightboxIndex - 1 + galleryItems.length) % galleryItems.length;
		updateLightboxContent(prevIndex);
	}

	// Gallery Card click & keyboard triggers
	galleryCards.forEach(card => {
		card.addEventListener('click', () => {
			const idx = parseInt(card.getAttribute('data-index'), 10);
			if (!isNaN(idx)) openLightbox(idx);
		});

		card.addEventListener('keydown', (e) => {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				const idx = parseInt(card.getAttribute('data-index'), 10);
				if (!isNaN(idx)) openLightbox(idx);
			}
		});
	});

	// Benefits card highlight preview triggers
	const highlightThumbs = document.querySelectorAll('.highlight-thumb[data-preview-index]');
	highlightThumbs.forEach(thumb => {
		thumb.addEventListener('click', () => {
			const idx = parseInt(thumb.getAttribute('data-preview-index'), 10);
			if (!isNaN(idx)) openLightbox(idx);
		});

		thumb.addEventListener('keydown', (e) => {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				const idx = parseInt(thumb.getAttribute('data-preview-index'), 10);
				if (!isNaN(idx)) openLightbox(idx);
			}
		});
	});

	// Lightbox navigation controls
	if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
	if (lightboxBackdrop) lightboxBackdrop.addEventListener('click', closeLightbox);
	if (lightboxNext) lightboxNext.addEventListener('click', nextLightbox);
	if (lightboxPrev) lightboxPrev.addEventListener('click', prevLightbox);

	// Lightbox Keyboard navigation
	window.addEventListener('keydown', (e) => {
		if (!lightboxModal || lightboxModal.hasAttribute('hidden')) return;

		if (e.key === 'Escape') {
			closeLightbox();
		} else if (e.key === 'ArrowRight') {
			nextLightbox();
		} else if (e.key === 'ArrowLeft') {
			prevLightbox();
		}
	});

	// Mobile Touch Swipe support for Lightbox
	let touchStartX = 0;
	let touchEndX = 0;

	if (lightboxModal) {
		lightboxModal.addEventListener('touchstart', (e) => {
			touchStartX = e.changedTouches[0].screenX;
		}, { passive: true });

		lightboxModal.addEventListener('touchend', (e) => {
			touchEndX = e.changedTouches[0].screenX;
			const diff = touchEndX - touchStartX;
			if (Math.abs(diff) > 45) {
				if (diff < 0) {
					nextLightbox();
				} else {
					prevLightbox();
				}
			}
		}, { passive: true });
	}

	// Category Filter Tabs
	const galleryTabs = document.querySelectorAll('.gallery-tab[data-filter]');
	const toggleMoreBtn = document.getElementById('galleryToggleMoreBtn');
	let galleryIsExpanded = false;

	function filterGallery(category) {
		galleryTabs.forEach(t => {
			const matches = t.getAttribute('data-filter') === category;
			t.classList.toggle('active', matches);
			t.setAttribute('aria-selected', String(matches));
		});

		if (category === 'all') {
			galleryCards.forEach((card, idx) => {
				if (galleryIsExpanded || idx < 12) {
					card.classList.remove('is-hidden');
				} else {
					card.classList.add('is-hidden');
				}
			});
			if (toggleMoreBtn) {
				toggleMoreBtn.style.display = '';
				const textSpan = toggleMoreBtn.querySelector('.btn-text');
				if (textSpan) {
					textSpan.textContent = galleryIsExpanded ? 'Show Less Moments' : 'View All 42 Training Photos';
				}
			}
		} else {
			galleryCards.forEach(card => {
				const cardCat = card.getAttribute('data-category');
				if (cardCat === category) {
					card.classList.remove('is-hidden');
				} else {
					card.classList.add('is-hidden');
				}
			});
			if (toggleMoreBtn) toggleMoreBtn.style.display = 'none';
		}
	}

	galleryTabs.forEach(tab => {
		tab.addEventListener('click', () => {
			const filter = tab.getAttribute('data-filter') || 'all';
			filterGallery(filter);
		});
	});

	// "View All 42 Training Photos / Show Less" toggle
	if (toggleMoreBtn) {
		toggleMoreBtn.addEventListener('click', () => {
			galleryIsExpanded = !galleryIsExpanded;
			toggleMoreBtn.classList.toggle('is-expanded', galleryIsExpanded);
			const textSpan = toggleMoreBtn.querySelector('.btn-text');

			if (galleryIsExpanded) {
				galleryCards.forEach(card => card.classList.remove('is-hidden'));
				if (textSpan) textSpan.textContent = 'Show Less Moments';
			} else {
				galleryCards.forEach((card, idx) => {
					if (idx >= 12) card.classList.add('is-hidden');
				});
				if (textSpan) textSpan.textContent = 'View All 42 Training Photos';
				const galleryElem = document.getElementById('training-gallery');
				if (galleryElem) galleryElem.scrollIntoView({ behavior: 'smooth' });
			}
		});
	}

	/* ==========================================================================
	   Back to Top Smooth Scroll
	   ========================================================================== */
	const backToTopBtn = document.getElementById('backToTopBtn');
	if (backToTopBtn) {
		backToTopBtn.addEventListener('click', (e) => {
			e.preventDefault();
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}

});
