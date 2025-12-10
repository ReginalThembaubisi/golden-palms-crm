# Images Directory

This directory contains images for the Golden Palms Beach Resort website.

## Current Setup

Currently, the website uses **Unsplash Source API** for placeholder images. This provides beautiful, high-quality stock photos perfect for demonstrations.

## Image Sources

### Placeholder Images (Current)
All images are loaded from Unsplash Source API:
- Format: `https://source.unsplash.com/featured/WIDTHxHEIGHT/?keywords`
- No API key required
- Free to use for demonstrations

### Replacing with Real Images

When you're ready to add your own photos:

1. **Hero Images**: Replace hero background images (1920x1080px recommended)
   - File: `hero-1.jpg`, `hero-2.jpg`, etc.
   - Update in `public/index.html` hero section

2. **Accommodation Images**: 
   - `2bedroom.jpg` - 2 Bedroom Unit photo (600x400px recommended)
   - `3bedroom.jpg` - 3 Bedroom Unit photo (600x400px recommended)
   - `5bedroom.jpg` - 5 Bedroom Unit photo (600x400px recommended)

3. **Gallery Images**:
   - `gallery-1.jpg` through `gallery-6.jpg` (800x600px recommended)
   - Various beach, resort, and activity photos

4. **Logo**:
   - `logo.png` - Resort logo (transparent background recommended)

## Image Optimization Tips

- Use JPEG for photos (smaller file size)
- Use PNG for logos (transparency support)
- Compress images before uploading
- Recommended max file size: 500KB per image
- Use tools like TinyPNG or ImageOptim for compression

## Current Placeholder URLs

The website currently uses these Unsplash Source URLs:
- Hero: `https://source.unsplash.com/featured/1920x1080/?beach,resort,mozambique`
- Accommodation: `https://source.unsplash.com/featured/600x400/?accommodation,hotel`
- Gallery: Various beach/tropical keywords

