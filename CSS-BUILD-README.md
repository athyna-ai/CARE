# Tailwind CSS Build Process

This project now uses a production-ready Tailwind CSS setup instead of the CDN version.

## Files Structure

```
├── src/
│   └── input.css          # Source CSS file with Tailwind directives
├── dist/
│   └── output.css         # Compiled production CSS
├── tailwind.config.js     # Tailwind configuration
├── package.json           # Node.js dependencies
├── build-css.bat          # Production build script
└── build-css-dev.bat      # Development build script
```

## Building CSS

### Production Build
Run `build-css.bat` to create a minified production build:
```bash
build-css.bat
```

### Development Build
Run `build-css-dev.bat` to watch for changes and rebuild automatically:
```bash
build-css-dev.bat
```

### Manual Build
You can also run the build commands manually:
```bash
# Production (minified)
npx tailwindcss -i ./src/input.css -o ./dist/output.css --minify

# Development (with watch)
npx tailwindcss -i ./src/input.css -o ./dist/output.css --watch
```

## Customization

### Adding New Classes
1. Add your custom classes to `src/input.css`
2. Run the build script to compile
3. The new classes will be available in `dist/output.css`

### Modifying Tailwind Config
Edit `tailwind.config.js` to:
- Add new colors
- Modify spacing
- Add custom fonts
- Configure plugins

## Benefits of This Setup

✅ **Production Ready**: No CDN dependency
✅ **Faster Loading**: Minified CSS file
✅ **Customizable**: Full control over Tailwind configuration
✅ **Optimized**: Only includes classes actually used
✅ **Offline**: Works without internet connection
✅ **Version Control**: CSS is part of your project

## Troubleshooting

If you get "command not found" errors:
1. Make sure Node.js is installed
2. Run `npm install` to install dependencies
3. Try using `npx` prefix: `npx tailwindcss ...`

## Updating Dependencies

To update Tailwind CSS:
```bash
npm update tailwindcss @tailwindcss/forms @tailwindcss/typography
```
