document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.querySelector('.site-navbar');
    const voiceNavButton = document.getElementById('voiceNavButton');
    const voiceNavToast = document.getElementById('voiceNavToast');

    function handleNavbarScroll() {
        if (!navbar) return;
        if (window.scrollY > 30) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    handleNavbarScroll();
    window.addEventListener('scroll', handleNavbarScroll);

    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        }
    });

    const revealItems = document.querySelectorAll('.reveal-up');

    if ('IntersectionObserver' in window && revealItems.length > 0) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15
        });

        revealItems.forEach(item => revealObserver.observe(item));
    } else {
        revealItems.forEach(item => item.classList.add('visible'));
    }

    const SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;
    const publicVoiceRoutes = [
        {
            name: 'Home',
            href: 'index.php',
            aliases: ['home', 'homepage'],
            intentKeywords: ['main page', 'start page', 'go home']
        },
        {
            name: 'About Us',
            href: 'about.php',
            aliases: ['about', 'about us'],
            intentKeywords: ['about the hotel', 'hotel story', 'resort story', 'about horizon sands']
        },
        {
            name: 'Suites & Villas',
            href: 'rooms_and_suites.php',
            aliases: ['room', 'rooms', 'rooms page', 'suites', 'suite', 'villa', 'villas', 'suites and villas', 'rooms and suites'],
            intentKeywords: ['book a room', 'find a room', 'show rooms', 'show me rooms', 'take me to rooms', 'stay options', 'room options', 'room types', 'villa options', 'suite options', 'accommodation']
        },
        {
            name: 'Amenities',
            href: 'amenities.php',
            aliases: ['amenities'],
            intentKeywords: ['pool', 'spa facilities', 'gym', 'fitness', 'hotel facilities', 'resort facilities']
        },
        {
            name: 'Dining',
            href: 'Dining.php',
            aliases: ['dining', 'restaurant'],
            intentKeywords: ['food', 'eat', 'where to eat', 'dinner', 'lunch', 'breakfast', 'restaurant options']
        },
        {
            name: 'Reviews',
            href: 'reviews.php',
            aliases: ['reviews', 'guest reviews'],
            intentKeywords: ['feedback', 'ratings', 'testimonials', 'what guests say']
        },
        {
            name: 'FAQs',
            href: 'FAQs.php',
            aliases: ['faq', 'faqs', 'questions'],
            intentKeywords: ['common questions', 'help questions', 'frequently asked questions']
        },
        {
            name: "What's Happening",
            href: 'whats_happening.php',
            aliases: ["what's happening", 'whats happening', 'happenings', 'events', 'around bali'],
            intentKeywords: ['plan a day out', 'day out', 'day trip', 'things to do', 'activities', 'explore bali', 'local attractions', 'outing', 'sightseeing']
        },
        {
            name: 'Parking & Transport',
            href: 'parking_and_transport.php',
            aliases: ['parking', 'transport', 'parking and transport', 'arrival guide'],
            intentKeywords: ['airport transfer', 'pickup', 'parking info', 'getting here', 'arrival help', 'directions to hotel']
        },
        {
            name: 'Contact',
            href: 'contact.php',
            aliases: ['contact', 'contact us'],
            intentKeywords: ['concierge', 'call the hotel', 'hotel phone', 'hotel email', 'get in touch', 'speak to someone']
        },
        {
            name: 'Login',
            href: 'login.php',
            aliases: ['login', 'log in', 'sign in'],
            intentKeywords: ['open login', 'take me to login', 'go to login', 'member login', 'guest login', 'sign me in']
        },
        {
            name: 'Register',
            href: 'register.php',
            aliases: ['register', 'sign up', 'signup', 'create account', 'create an account'],
            intentKeywords: ['open register', 'take me to register', 'go to register', 'make an account', 'new account', 'open sign up']
        },
        {
            name: 'Policies',
            href: 'policies.php',
            aliases: ['policies', 'hotel policies'],
            intentKeywords: ['check in policy', 'checkout policy', 'cancellation policy', 'hotel rules', 'smoking policy', 'pet policy']
        }
    ];

    let recognition = null;
    let isListening = false;
    let handledResult = false;
    let toastTimer = null;

    function isSensitiveAuthPage() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        return currentPage === 'login.php' || currentPage === 'register.php';
    }

    function showVoiceToast(message, duration) {
        if (!voiceNavToast) return;

        if (toastTimer) {
            clearTimeout(toastTimer);
        }

        voiceNavToast.textContent = message;
        voiceNavToast.hidden = false;

        toastTimer = window.setTimeout(function () {
            voiceNavToast.hidden = true;
        }, typeof duration === 'number' ? duration : 3200);
    }

    function setVoiceButtonState(state) {
        if (!voiceNavButton) return;

        voiceNavButton.classList.remove('is-listening', 'is-busy', 'is-unsupported');

        if (state === 'listening') {
            voiceNavButton.classList.add('is-listening');
            voiceNavButton.setAttribute('aria-label', 'Stop voice navigation');
            return;
        }

        if (state === 'busy') {
            voiceNavButton.classList.add('is-busy');
            voiceNavButton.setAttribute('aria-label', 'Voice navigation is processing');
            return;
        }

        if (state === 'unsupported') {
            voiceNavButton.classList.add('is-unsupported');
            voiceNavButton.setAttribute('aria-label', 'Voice navigation is not supported in this browser');
            return;
        }

        voiceNavButton.setAttribute('aria-label', 'Open voice navigation');
    }

    function normalizeSpeechText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/&/g, ' and ')
            .replace(/[^a-z0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function simplifyVoiceCommand(value) {
        return normalizeSpeechText(value)
            .replace(/\b(?:please|now|thanks|thank you)\b/g, ' ')
            .replace(/\b(?:can you|could you|would you|i want to|i want|i need to|help me|let me)\b/g, ' ')
            .replace(/\b(?:take me to|bring me to|go to|open|show me|show|navigate to|bring me|head to|view)\b/g, ' ')
            .replace(/\b(?:the|a|an|page|section|screen|website|site)\b/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getRoutePatterns(route) {
        return route.aliases.concat(route.intentKeywords || []);
    }

    function getTranscriptWordSet(value) {
        return new Set(normalizeSpeechText(value).split(' ').filter(Boolean));
    }

    function scoreRouteMatch(route, normalizedTranscript) {
        let bestScore = 0;
        const transcriptWords = getTranscriptWordSet(normalizedTranscript);

        getRoutePatterns(route).forEach(function (pattern) {
            const normalizedPattern = normalizeSpeechText(pattern);
            if (!normalizedPattern) {
                return;
            }

            if (normalizedTranscript === normalizedPattern) {
                bestScore = Math.max(bestScore, 140 + normalizedPattern.length);
                return;
            }

            if (normalizedTranscript.includes(normalizedPattern)) {
                bestScore = Math.max(bestScore, 90 + normalizedPattern.length);
                return;
            }

            const patternWords = normalizedPattern.split(' ');
            const overlap = patternWords.filter(function (word) {
                return transcriptWords.has(word);
            }).length;

            if (overlap > 0) {
                bestScore = Math.max(bestScore, overlap * 15 + normalizedPattern.length);
            }
        });

        return bestScore;
    }

    function findVoiceRouteMatch(transcript) {
        const transcriptCandidates = [
            normalizeSpeechText(transcript),
            simplifyVoiceCommand(transcript)
        ].filter(Boolean);

        let bestMatch = null;

        transcriptCandidates.forEach(function (normalizedTranscript) {
            const exactMatch = publicVoiceRoutes.find(function (route) {
                return getRoutePatterns(route).some(function (pattern) {
                    return normalizeSpeechText(pattern) === normalizedTranscript;
                });
            });

            if (exactMatch) {
                const score = 220 + normalizedTranscript.length;

                if (!bestMatch || score > bestMatch.score) {
                    bestMatch = {
                        route: exactMatch,
                        score: score,
                        transcript: normalizedTranscript
                    };
                }

                return;
            }

            const scoredMatches = publicVoiceRoutes
                .map(function (route) {
                    return {
                        route: route,
                        score: scoreRouteMatch(route, normalizedTranscript)
                    };
                })
                .filter(function (item) {
                    return item.score > 0;
                })
                .sort(function (a, b) {
                    return b.score - a.score;
                });

            if (!scoredMatches.length || scoredMatches[0].score < 25) {
                return;
            }

            if (!bestMatch || scoredMatches[0].score > bestMatch.score) {
                bestMatch = {
                    route: scoredMatches[0].route,
                    score: scoredMatches[0].score,
                    transcript: normalizedTranscript
                };
            }
        });

        return bestMatch;
    }

    function pickBestVoiceRouteFromAlternatives(resultsList) {
        let bestMatch = null;

        Array.from(resultsList || []).forEach(function (alternative) {
            const transcript = alternative && alternative.transcript ? alternative.transcript.trim() : '';
            if (!transcript) {
                return;
            }

            const routeMatch = findVoiceRouteMatch(transcript);
            if (!routeMatch) {
                return;
            }

            const confidenceBoost = typeof alternative.confidence === 'number'
                ? Math.round(alternative.confidence * 100)
                : 0;
            const totalScore = routeMatch.score + confidenceBoost;

            if (!bestMatch || totalScore > bestMatch.score) {
                bestMatch = {
                    route: routeMatch.route,
                    score: totalScore,
                    heardText: transcript
                };
            }
        });

        return bestMatch;
    }

    function navigateFromVoiceMatch(match, fallbackTranscript) {
        const matchedRoute = match ? match.route : null;
        const transcript = match && match.heardText ? match.heardText : fallbackTranscript;
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';

        if (!matchedRoute) {
            showVoiceToast('I heard "' + transcript + '" but could not match a page. Try Home, Rooms, Dining, Login, Register, Contact, or FAQs.');
            return;
        }

        if (matchedRoute.href === currentPage) {
            showVoiceToast('You are already on that page.');
            return;
        }

        showVoiceToast('Opening ' + matchedRoute.name + '...');
        window.setTimeout(function () {
            window.location.href = matchedRoute.href;
        }, 300);
    }

    function stopRecognition() {
        if (!recognition || !isListening) return;
        recognition.stop();
    }

    if (voiceNavButton) {
        if (!SpeechRecognitionApi) {
            setVoiceButtonState('unsupported');
            voiceNavButton.addEventListener('click', function () {
                showVoiceToast('Voice navigation works only in supported browsers.');
            });
        } else {
            recognition = new SpeechRecognitionApi();
            recognition.lang = (navigator.language && navigator.language.toLowerCase().startsWith('en'))
                ? navigator.language
                : 'en-US';
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.maxAlternatives = 5;

            recognition.addEventListener('start', function () {
                isListening = true;
                handledResult = false;
                setVoiceButtonState('listening');
                if (isSensitiveAuthPage()) {
                    showVoiceToast('Listening for navigation only. Do not say passwords or personal details. Try "login", "register", or a page name.');
                    return;
                }

                showVoiceToast('Listening... Try "plan a day out", "I want dinner", "login", "register", or a page name.');
            });

            recognition.addEventListener('result', function (event) {
                const alternatives = event.results && event.results[0] ? event.results[0] : [];
                const primaryTranscript = alternatives[0] && alternatives[0].transcript
                    ? alternatives[0].transcript.trim()
                    : '';
                const bestMatch = pickBestVoiceRouteFromAlternatives(alternatives);
                const transcript = bestMatch && bestMatch.heardText ? bestMatch.heardText : primaryTranscript;

                handledResult = transcript !== '';
                setVoiceButtonState('busy');

                if (!transcript) {
                    showVoiceToast('I could not hear a clear command. Please try again.');
                    return;
                }

                showVoiceToast('I heard "' + transcript + '".', 2200);
                navigateFromVoiceMatch(bestMatch, transcript);
            });

            recognition.addEventListener('error', function (event) {
                isListening = false;
                setVoiceButtonState('idle');

                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    showVoiceToast('Microphone permission was denied. Please allow mic access and try again.');
                    return;
                }

                if (event.error === 'no-speech') {
                    showVoiceToast('No speech detected. Tap the microphone and try again.');
                    return;
                }

                showVoiceToast('Voice navigation is unavailable right now. Please try again.');
            });

            recognition.addEventListener('end', function () {
                const shouldHintRetry = isListening && !handledResult;

                isListening = false;
                setVoiceButtonState('idle');

                if (shouldHintRetry) {
                    showVoiceToast('Listening stopped before a command was captured. Try again.');
                }
            });

            voiceNavButton.addEventListener('click', function () {
                if (isListening) {
                    stopRecognition();
                    showVoiceToast('Voice navigation stopped.');
                    return;
                }

                try {
                    recognition.start();
                } catch (error) {
                    showVoiceToast('Voice navigation is busy. Please wait a moment and try again.');
                }
            });
        }
    }
});
