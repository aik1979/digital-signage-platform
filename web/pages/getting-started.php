<?php
// No special logic needed for this page
?>

<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Getting Started Guide</h1>
            <p class="text-gray-400">Learn how to set up your digital signage in 5 easy steps</p>
        </div>
    </div>

    <!-- Introduction -->
    <div class="bg-gradient-to-r from-dsp-blue to-blue-600 rounded-lg p-8 text-white">
        <h2 class="text-2xl font-bold mb-4">Welcome to the Digital Signage Platform!</h2>
        <p class="text-lg">This guide will walk you through the entire process of setting up and displaying your digital signage content directly in a web browser. By the end of this guide, you will have your content playing on a public URL that you can use on any device with a web browser.</p>
    </div>

    <!-- Step 1: Upload Content -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-12 h-12 bg-dsp-blue rounded-full flex items-center justify-center text-white font-bold text-xl">
                1
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-bold text-white mb-3">Upload Your Content</h3>
                <p class="text-gray-300 mb-4">The first step is to upload the images and videos you want to display. All your media files are managed in the <strong>Content Library</strong>.</p>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 space-y-2 mb-4">
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">1.</span> Navigate to the <strong>Content</strong> page from the main menu.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">2.</span> Click the <strong>"⬆️ Upload Content"</strong> button in the top-right corner.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">3.</span> Select your files (JPG, PNG images or MP4 videos). You can select multiple files at once.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">4.</span> Set a default display duration in seconds for images. Videos will use their actual length.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">5.</span> Click the <strong>"Upload"</strong> button. Your files will be uploaded and appear in your Content Library.</p>
                </div>

                <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-300"><strong>💡 Tip:</strong> Supported formats are JPG, PNG for images and MP4 for videos. Keep file sizes reasonable for faster loading.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Create a Screen -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-12 h-12 bg-dsp-blue rounded-full flex items-center justify-center text-white font-bold text-xl">
                2
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-bold text-white mb-3">Create a Screen</h3>
                <p class="text-gray-300 mb-4">A "Screen" is a virtual display that you will assign content to. You can create as many screens as you need. For this guide, we will create one screen that will be displayed in a web browser.</p>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 space-y-2 mb-4">
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">1.</span> Navigate to the <strong>Screens</strong> page from the main menu.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">2.</span> Click the <strong>"➕ Add Screen"</strong> button.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">3.</span> Give your screen a descriptive name, such as "Lobby Display" or "Web Browser Screen".</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">4.</span> Click the <strong>"Add Screen"</strong> button. Your new screen will appear in the list.</p>
                </div>

                <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-300"><strong>💡 Tip:</strong> Each screen gets a unique device key that allows it to connect securely to your account.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Create a Playlist -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-12 h-12 bg-dsp-blue rounded-full flex items-center justify-center text-white font-bold text-xl">
                3
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-bold text-white mb-3">Create a Playlist</h3>
                <p class="text-gray-300 mb-4">A Playlist is a collection of content items that you can arrange in a specific order. You can then assign a playlist to one or more screens.</p>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 space-y-2 mb-4">
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">1.</span> Navigate to the <strong>Playlists</strong> page from the main menu.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">2.</span> Click the <strong>"➕ Create Playlist"</strong> button.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">3.</span> Give your playlist a name, like "Morning Announcements" or "Product Showcase".</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">4.</span> Click <strong>"Create Playlist"</strong> to save it.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">5.</span> Click <strong>"✏️ Edit"</strong> on your new playlist to add content to it.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">6.</span> In the editor, you'll see your content library on the right. Click on any content item to add it to the playlist.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">7.</span> You can adjust the duration for each item and drag to reorder them.</p>
                </div>

                <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-300"><strong>💡 Tip:</strong> You can create multiple playlists for different purposes and switch between them on your screens.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Assign Playlist to Screen -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-12 h-12 bg-dsp-blue rounded-full flex items-center justify-center text-white font-bold text-xl">
                4
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-bold text-white mb-3">Assign a Playlist to a Screen</h3>
                <p class="text-gray-300 mb-4">Once you have a playlist with content, you need to assign it to a screen to be displayed.</p>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 space-y-2 mb-4">
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">1.</span> Go back to the <strong>Screens</strong> page.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">2.</span> Find the screen you created earlier and click the <strong>"✏️ Edit"</strong> button.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">3.</span> In the "Current Playlist" dropdown, select the playlist you want to display.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">4.</span> Click <strong>"Update Screen"</strong> to save the assignment.</p>
                </div>

                <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-300"><strong>💡 Tip:</strong> You can change the playlist assignment at any time, and the screen will automatically update.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 5: View Your Screen -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                5
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-bold text-white mb-3">View Your Screen in a Web Browser</h3>
                <p class="text-gray-300 mb-4">Now that your screen is set up and has a playlist assigned, you can view it in any web browser. Each screen has a unique, public URL that you can open on any device.</p>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 space-y-2 mb-4">
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">1.</span> Go to the <strong>Screens</strong> page.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">2.</span> Find your screen and look for the device key (starts with "DSP-").</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">3.</span> Click the <strong>"👁️ View"</strong> button to open the viewer in a new tab.</p>
                    <p class="text-gray-300"><span class="text-dsp-blue font-semibold">4.</span> Your playlist will begin playing in full-screen mode!</p>
                </div>

                <div class="bg-green-900 bg-opacity-30 border border-green-700 rounded-lg p-4 mb-4">
                    <p class="text-green-300"><strong>🎉 Congratulations!</strong> You have successfully set up your first digital sign using the web browser version. You can now use this URL on any smart TV, computer, or tablet to display your content.</p>
                </div>

                <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-300"><strong>💡 Tip:</strong> Bookmark the viewer URL or set it as the homepage on your display device for easy access. The content will automatically update when you change the playlist.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="bg-gradient-to-r from-purple-900 to-purple-800 border border-purple-700 rounded-lg p-6">
        <h3 class="text-2xl font-bold text-white mb-4">🚀 Next Steps</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-white">
            <div class="bg-black bg-opacity-30 rounded-lg p-4">
                <h4 class="font-bold mb-2">📅 Schedule Content</h4>
                <p class="text-sm text-gray-300">Use the Schedules page to automatically switch playlists at specific times or days.</p>
            </div>
            <div class="bg-black bg-opacity-30 rounded-lg p-4">
                <h4 class="font-bold mb-2">🔄 Update Content</h4>
                <p class="text-sm text-gray-300">Upload new content anytime and add it to your playlists. Changes appear instantly on your screens.</p>
            </div>
            <div class="bg-black bg-opacity-30 rounded-lg p-4">
                <h4 class="font-bold mb-2">📱 Multiple Screens</h4>
                <p class="text-sm text-gray-300">Create multiple screens for different locations, each with its own playlist.</p>
            </div>
            <div class="bg-black bg-opacity-30 rounded-lg p-4">
                <h4 class="font-bold mb-2">🍓 Raspberry Pi Setup</h4>
                <p class="text-sm text-gray-300">Set up a dedicated Raspberry Pi display with our one-line installer.</p>
                <a href="/raspberry-pi-setup.php" class="inline-block mt-2 text-blue-400 hover:text-blue-300 text-sm font-semibold">
                    View Setup Guide →
                </a>
            </div>
        </div>
    </div>

    <!-- Need Help? -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center">
        <h3 class="text-2xl font-bold text-white mb-3">Need Help?</h3>
        <p class="text-gray-300 mb-4">If you have any questions or run into issues, check out our documentation or contact support.</p>
        <div class="flex justify-center space-x-4">
            <a href="?page=dashboard" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
                Go to Dashboard
            </a>
            <a href="?page=content" class="bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-green-600 hover:to-green-700 transition transform hover:scale-105 shadow-lg">
                Upload Content
            </a>
        </div>
    </div>
</div>
