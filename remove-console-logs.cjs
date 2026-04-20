#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Function to recursively find all JS/JSX files
function findJSFiles(dir, fileList = []) {
    const files = fs.readdirSync(dir);

    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);

        if (stat.isDirectory()) {
            findJSFiles(filePath, fileList);
        } else if (file.match(/\.(js|jsx|ts|tsx)$/)) {
            fileList.push(filePath);
        }
    });

    return fileList;
}

// Function to remove console statements from a file
function removeConsoleStatements(filePath) {
    try {
        let content = fs.readFileSync(filePath, 'utf8');
        const originalContent = content;

        // Remove console.log, console.error, console.warn, console.info, console.debug
        // But preserve commented console statements
        content = content.replace(/^(\s*)console\.(log|error|warn|info|debug|trace)\([^)]*\);?\s*$/gm, '');

        // Remove console statements that span multiple lines
        content = content.replace(/^(\s*)console\.(log|error|warn|info|debug|trace)\([^)]*$/gm, (match, indent) => {
            // Find the closing parenthesis
            const lines = content.split('\n');
            let startIndex = -1;
            let endIndex = -1;

            for (let i = 0; i < lines.length; i++) {
                if (lines[i].includes(match.trim())) {
                    startIndex = i;
                    break;
                }
            }

            if (startIndex !== -1) {
                let openParens = 0;
                for (let i = startIndex; i < lines.length; i++) {
                    const line = lines[i];
                    for (let char of line) {
                        if (char === '(') openParens++;
                        if (char === ')') openParens--;
                        if (openParens === 0 && char === ')') {
                            endIndex = i;
                            break;
                        }
                    }
                    if (endIndex !== -1) break;
                }
            }

            return '';
        });

        // Clean up empty lines
        content = content.replace(/\n\s*\n\s*\n/g, '\n\n');

        if (content !== originalContent) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log(`✓ Cleaned console statements from: ${filePath}`);
            return true;
        }

        return false;
    } catch (error) {
        console.error(`✗ Error processing ${filePath}:`, error.message);
        return false;
    }
}

// Main execution
const resourcesJsDir = path.join(__dirname, 'resources', 'js');

if (!fs.existsSync(resourcesJsDir)) {
    console.error('resources/js directory not found!');
    process.exit(1);
}

console.log('🧹 Removing console statements from JavaScript files...\n');

const jsFiles = findJSFiles(resourcesJsDir);
let cleanedCount = 0;

jsFiles.forEach(file => {
    if (removeConsoleStatements(file)) {
        cleanedCount++;
    }
});

console.log(`\n✅ Completed! Cleaned ${cleanedCount} files out of ${jsFiles.length} total files.`);

if (cleanedCount > 0) {
    console.log('\n⚠️  Remember to test your application after removing console statements!');
}