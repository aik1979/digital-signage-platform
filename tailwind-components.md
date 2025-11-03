# Tailwind CSS Components for DSP

## Buttons

### Primary Button
```html
<button class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
    Button Text
</button>
```

### Secondary Button
```html
<button class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition">
    Button Text
</button>
```

### Danger Button
```html
<button class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-red-600 hover:to-red-700 transition transform hover:scale-105 shadow-lg">
    Delete
</button>
```

### Small Button
```html
<button class="bg-dsp-blue text-white font-semibold py-1.5 px-4 text-sm rounded-md hover:bg-blue-600 transition">
    Small
</button>
```

## Form Elements

### Input Field
```html
<input type="text" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
```

### Select Dropdown
```html
<select class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
    <option>Option 1</option>
</select>
```

### Textarea
```html
<textarea class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition" rows="3"></textarea>
```

### Label
```html
<label class="block text-sm font-medium text-gray-300 mb-2">Label Text</label>
```

## Cards

### Standard Card
```html
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
    <h2 class="text-2xl font-bold text-white mb-4">Card Title</h2>
    <!-- Content -->
</div>
```

### Gradient Card
```html
<div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
    <!-- Content -->
</div>
```

## Modals

### Modal Container
```html
<div id="modalId" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-gray-700">
        <!-- Modal content -->
    </div>
</div>
```

### Modal Header
```html
<div class="flex items-center justify-between p-6 border-b border-gray-700">
    <h2 class="text-2xl font-bold text-white">Modal Title</h2>
    <button onclick="closeModal()" class="text-gray-400 hover:text-white text-3xl leading-none">&times;</button>
</div>
```

### Modal Body
```html
<div class="p-6 space-y-4">
    <!-- Content -->
</div>
```

### Modal Footer
```html
<div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
    <button class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition">Cancel</button>
    <button class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Submit</button>
</div>
```

## Tables

### Table Container
```html
<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-700">
                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Header</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                <td class="py-3 px-4 text-white">Data</td>
            </tr>
        </tbody>
    </table>
</div>
```

## Badges

### Success Badge
```html
<span class="inline-block bg-green-600 text-white text-xs px-2 py-1 rounded">Online</span>
```

### Error Badge
```html
<span class="inline-block bg-red-600 text-white text-xs px-2 py-1 rounded">Offline</span>
```

### Info Badge
```html
<span class="inline-block bg-blue-600 text-white text-xs px-2 py-1 rounded">Info</span>
```

## Page Layouts

### Page Header
```html
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Page Title</h1>
        <p class="text-gray-400">Page description</p>
    </div>
    <button class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
        ➕ Add New
    </button>
</div>
```

### Empty State
```html
<div class="bg-gray-800 border border-gray-700 rounded-lg p-12 text-center">
    <div class="text-6xl mb-4">📺</div>
    <h2 class="text-2xl font-bold text-white mb-2">No Items Yet</h2>
    <p class="text-gray-400 mb-6">Get started by adding your first item.</p>
    <button class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
        Add First Item
    </button>
</div>
```

## Alerts

### Success Alert
```html
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
    Success message
</div>
```

### Error Alert
```html
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
    Error message
</div>
```
