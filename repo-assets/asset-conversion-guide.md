# Asset Conversion Guide

## Converting SVG Templates to PNG

### Required Tools
1. **Inkscape** (Free, recommended)
   - Download: https://inkscape.org/
   - Best for SVG to PNG conversion
   - Maintains quality and supports gradients

2. **Adobe Illustrator** (Paid alternative)
   - Professional vector graphics editor
   - Excellent SVG support

3. **Online Converters** (Quick option)
   - CloudConvert.com
   - Convertio.co
   - SVG2PNG.com

### Conversion Steps (Using Inkscape)

#### Banner Conversion
1. Open `banner-template.svg` in Inkscape
2. Go to **File > Export PNG Image**
3. Set export area to **Page**
4. Set width to **1544 pixels** (height will auto-adjust to 500)
5. Set DPI to **96**
6. Export as `banner-1544x500.png`

For high-DPI version:
1. Set width to **3088 pixels** (height will auto-adjust to 1000)
2. Export as `banner-3088x1000.png`

#### Icon Conversion
1. Open `icon-template.svg` in Inkscape
2. Go to **File > Export PNG Image**
3. Set export area to **Page**
4. Set width to **256 pixels** (height will auto-adjust to 256)
5. Set DPI to **96**
6. Export as `icon-256x256.png`

For high-DPI version:
1. Set width to **512 pixels**
2. Export as `icon-512x512.png`

### Command Line Conversion (Advanced)

If you have Inkscape installed, you can use command line:

```bash
# Banner conversions
inkscape --export-png=banner-1544x500.png --export-width=1544 banner-template.svg
inkscape --export-png=banner-3088x1000.png --export-width=3088 banner-template.svg

# Icon conversions
inkscape --export-png=icon-256x256.png --export-width=256 icon-template.svg
inkscape --export-png=icon-512x512.png --export-width=512 icon-template.svg
```

### Quality Optimization

After conversion, optimize the PNG files:

1. **TinyPNG** (Online)
   - Visit: https://tinypng.com/
   - Upload PNG files for compression
   - Download optimized versions

2. **ImageOptim** (Mac)
   - Drag and drop PNG files
   - Automatic optimization

3. **OptiPNG** (Command line)
   ```bash
   optipng -o7 banner-1544x500.png
   optipng -o7 icon-256x256.png
   ```

### File Size Requirements
- **Banner:** Under 1MB
- **Icon:** Under 500KB
- **Screenshots:** Under 1MB each

### Final File Structure
```
repo-assets/
├── banner-1544x500.png
├── banner-3088x1000.png
├── icon-256x256.png
├── icon-512x512.png
├── screenshot-1.png
├── screenshot-2.png
├── screenshot-3.png
├── screenshot-4.png
├── screenshot-5.png
└── screenshot-6.png
```

## Customization Options

### Banner Customization
- **Colors:** Modify gradient stops in SVG
- **Text:** Edit text elements directly in SVG
- **Logo:** Replace icon elements with custom design
- **Features:** Update feature list text

### Icon Customization
- **Colors:** Adjust gradient definitions
- **Symbol:** Modify server/monitoring icon elements
- **Effects:** Add or remove shadow/glow effects
- **Background:** Change rounded corner radius

### Brand Consistency
- Use consistent color scheme across all assets
- Maintain readable typography
- Ensure logo/branding is clearly visible
- Test visibility at different sizes

## WordPress Repository Upload

1. **Create SVN Account:** Register at WordPress.org
2. **Access Plugin SVN:** Get SVN access for your plugin
3. **Upload to Assets Folder:** Place all PNG files in `/assets/` directory
4. **Commit Changes:** SVN commit to make assets live
5. **Verify Display:** Check plugin page for proper display

## Troubleshooting

### Common Issues:
- **Blurry Images:** Ensure exact pixel dimensions
- **Large File Size:** Use PNG optimization tools
- **Color Issues:** Check color profile settings
- **SVG Not Rendering:** Verify SVG syntax and gradients

### Quality Checklist:
- ✅ Exact dimensions as specified
- ✅ PNG format with transparency support
- ✅ Optimized file size under limits
- ✅ Clear, readable text at all sizes
- ✅ Consistent branding and colors
- ✅ Professional appearance