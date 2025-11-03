/**
 * Guided Tour System using Intro.js
 * Provides interactive walkthroughs for each page
 */

// Get current page from URL
function getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('page') || 'dashboard';
}

// Get tour steps for a specific page (executed when tour starts, not on script load)
function getTourSteps(page) {
    const tourDefinitions = {
        dashboard: [
            {
                intro: "Welcome to your Digital Signage Platform! 🎉 Let's take a quick tour to help you get started."
            },
            {
                element: 'a[href="?page=screens"]',
                intro: "Manage your screens here. Create virtual displays that will show your content.",
                position: 'bottom'
            },
            {
                element: 'a[href="?page=content"]',
                intro: "Upload and manage your images and videos in the Content Library.",
                position: 'bottom'
            },
            {
                element: 'a[href="?page=playlists"]',
                intro: "Create playlists to organize your content and control what plays on each screen.",
                position: 'bottom'
            },
            {
                element: 'a[href="?page=schedules"]',
                intro: "Schedule when different playlists should play automatically.",
                position: 'bottom'
            },
            {
                element: 'a[href="?page=getting-started"]',
                intro: "New here? Check out the Getting Started Guide for step-by-step instructions!",
                position: 'bottom'
            },
            {
                intro: "That's it! Click 'Done' to start using the platform. You can restart this tour anytime by clicking the 🎯 Tour button."
            }
        ],
        
        screens: [
            {
                intro: "Welcome to the Screens page! Here you can manage all your digital displays."
            },
            {
                intro: "Click the '➕ Add Screen' button to create a new screen. Each screen gets a unique device key that allows it to connect to your account."
            },
            {
                intro: "After creating a screen, you can assign a playlist to it and get a viewer URL to display your content!"
            }
        ],
        
        content: [
            {
                intro: "Welcome to your Content Library! This is where all your media files are stored."
            },
            {
                intro: "Click the '⬆️ Upload Content' button to upload new images or videos. Supported formats: JPG, PNG for images and MP4 for videos."
            },
            {
                intro: "Once uploaded, you can add your content to playlists and display them on your screens!"
            }
        ],
        
        playlists: [
            {
                intro: "Welcome to Playlists! Here you organize your content into sequences."
            },
            {
                intro: "Click '➕ Create Playlist' to create a new playlist. Give it a name and choose a transition effect."
            },
            {
                intro: "After creating a playlist, click '✏️ Edit' to add content items. You can click items to add them and drag to reorder!"
            }
        ],
        
        schedules: [
            {
                intro: "Welcome to Schedules! Automate when different playlists play on your screens."
            },
            {
                intro: "Create time-based rules to automatically switch playlists. Perfect for showing different content at different times of day!"
            }
        ],
        
        settings: [
            {
                intro: "Welcome to Settings! Manage your account preferences here."
            },
            {
                intro: "You can update your profile, change your password, and customize your platform experience."
            },
            {
                intro: "Don't forget to check out the 🎯 Guided Tour Preferences section to control when tours appear!"
            }
        ],
        
        'getting-started': [
            {
                intro: "This is the Getting Started Guide! Follow the 5 steps to set up your first digital sign."
            },
            {
                intro: "The guide covers everything from uploading content to viewing it in a web browser. Take your time and follow each step!"
            }
        ]
    };
    
    return tourDefinitions[page] || null;
}

// Start the tour for the current page
function startTour() {
    const currentPage = getCurrentPage();
    const tourSteps = getTourSteps(currentPage);
    
    if (!tourSteps) {
        alert('No tour available for this page.');
        return;
    }
    
    // Convert selector strings to actual elements
    const steps = tourSteps.map(step => {
        if (step.element && typeof step.element === 'string') {
            const element = document.querySelector(step.element);
            if (!element) {
                // If element not found, return intro-only step
                return {
                    intro: step.intro
                };
            }
            return {
                element: element,
                intro: step.intro,
                position: step.position || 'bottom'
            };
        }
        return step;
    });
    
    // Filter out any null steps
    const validSteps = steps.filter(step => step !== null);
    
    if (validSteps.length === 0) {
        alert('Tour cannot start - no valid steps found.');
        return;
    }
    
    const intro = introJs();
    intro.setOptions({
        steps: validSteps,
        showProgress: true,
        showBullets: false,
        exitOnOverlayClick: false,
        doneLabel: 'Done',
        nextLabel: 'Next →',
        prevLabel: '← Back',
        skipLabel: 'Skip Tour'
    });
    
    intro.start();
    
    // Mark tour as seen for this session
    sessionStorage.setItem(`tour_seen_${currentPage}`, 'true');
}

// Check if user has disabled tours permanently
function isTourDisabled() {
    return localStorage.getItem('tour_disabled') === 'true';
}

// Disable tours permanently
function disableTour() {
    localStorage.setItem('tour_disabled', 'true');
}

// Enable tours again
function enableTour() {
    localStorage.removeItem('tour_disabled');
}

// Auto-start tour on page load if not seen before
function autoStartTour() {
    const currentPage = getCurrentPage();
    
    // Don't auto-start if:
    // 1. User has permanently disabled tours
    // 2. Tour has been seen in this session
    // 3. No tour exists for this page
    if (isTourDisabled()) {
        console.log('Tours are disabled by user preference');
        return;
    }
    
    if (sessionStorage.getItem(`tour_seen_${currentPage}`) === 'true') {
        console.log('Tour already seen in this session');
        return;
    }
    
    if (!getTourSteps(currentPage)) {
        console.log('No tour available for page:', currentPage);
        return;
    }
    
    // Auto-start after a short delay to let the page render
    console.log('Auto-starting tour for page:', currentPage);
    setTimeout(() => {
        startTour();
    }, 1500);
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoStartTour);
} else {
    autoStartTour();
}
