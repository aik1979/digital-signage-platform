/**
 * Guided Tour System using Intro.js
 * Provides interactive walkthroughs for each page
 */

// Tour definitions for each page
const tours = {
    dashboard: {
        steps: [
            {
                intro: "Welcome to your Digital Signage Platform! 🎉 Let's take a quick tour to help you get started."
            },
            {
                element: document.querySelector('a[href="?page=screens"]'),
                intro: "Manage your screens here. Create virtual displays that will show your content.",
                position: 'bottom'
            },
            {
                element: document.querySelector('a[href="?page=content"]'),
                intro: "Upload and manage your images and videos in the Content Library.",
                position: 'bottom'
            },
            {
                element: document.querySelector('a[href="?page=playlists"]'),
                intro: "Create playlists to organize your content and control what plays on each screen.",
                position: 'bottom'
            },
            {
                element: document.querySelector('a[href="?page=schedules"]'),
                intro: "Schedule when different playlists should play automatically.",
                position: 'bottom'
            },
            {
                element: document.querySelector('a[href="?page=getting-started"]'),
                intro: "New here? Check out the Getting Started Guide for step-by-step instructions!",
                position: 'bottom'
            },
            {
                intro: "That's it! Click 'Done' to start using the platform. You can restart this tour anytime by clicking the 🎯 Tour button."
            }
        ]
    },
    
    screens: {
        steps: [
            {
                intro: "Welcome to the Screens page! Here you can manage all your digital displays."
            },
            {
                element: document.querySelector('button:has-text("Add Screen"), button:contains("Add Screen"), .btn-primary'),
                intro: "Click here to create a new screen. Each screen gets a unique device key.",
                position: 'left'
            },
            {
                intro: "After creating a screen, you can assign a playlist to it and get a viewer URL to display your content!"
            }
        ]
    },
    
    content: {
        steps: [
            {
                intro: "Welcome to your Content Library! This is where all your media files are stored."
            },
            {
                element: document.querySelector('button:has-text("Upload"), button:contains("Upload")'),
                intro: "Click here to upload new images or videos. Supported formats: JPG, PNG, MP4.",
                position: 'left'
            },
            {
                intro: "Once uploaded, you can add your content to playlists and display them on your screens!"
            }
        ]
    },
    
    playlists: {
        steps: [
            {
                intro: "Welcome to Playlists! Here you organize your content into sequences."
            },
            {
                element: document.querySelector('button:has-text("Create Playlist"), button:contains("Create Playlist")'),
                intro: "Click here to create a new playlist.",
                position: 'left'
            },
            {
                intro: "After creating a playlist, click Edit to add content items. You can drag to reorder them and set custom durations!"
            }
        ]
    },
    
    schedules: {
        steps: [
            {
                intro: "Welcome to Schedules! Automate when different playlists play on your screens."
            },
            {
                intro: "Create time-based rules to automatically switch playlists. Perfect for showing different content at different times of day!"
            }
        ]
    },
    
    settings: {
        steps: [
            {
                intro: "Welcome to Settings! Manage your account preferences here."
            },
            {
                intro: "You can update your profile, change your password, and customize your platform experience."
            }
        ]
    },
    
    'getting-started': {
        steps: [
            {
                intro: "This is the Getting Started Guide! Follow the 5 steps to set up your first digital sign."
            },
            {
                intro: "The guide covers everything from uploading content to viewing it in a web browser. Take your time and follow each step!"
            }
        ]
    }
};

// Get current page from URL
function getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('page') || 'dashboard';
}

// Start the tour for the current page
function startTour() {
    const currentPage = getCurrentPage();
    const tour = tours[currentPage];
    
    if (!tour) {
        console.warn('No tour defined for page:', currentPage);
        return;
    }
    
    // Filter out steps with missing elements
    const validSteps = tour.steps.filter(step => {
        if (!step.element) return true; // Keep intro-only steps
        return step.element !== null; // Only keep steps where element exists
    });
    
    if (validSteps.length === 0) {
        console.warn('No valid tour steps found for page:', currentPage);
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
        return;
    }
    
    if (sessionStorage.getItem(`tour_seen_${currentPage}`) === 'true') {
        return;
    }
    
    if (!tours[currentPage]) {
        return;
    }
    
    // Auto-start after a short delay to let the page render
    setTimeout(() => {
        startTour();
    }, 1000);
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoStartTour);
} else {
    autoStartTour();
}
