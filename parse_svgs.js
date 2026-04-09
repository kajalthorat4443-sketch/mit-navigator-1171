const fs = require('fs');
const path = require('path');
const dir = 'e:/Copriight/MIT-Institute-Map-System/assets/images';
const files = fs.readdirSync(dir).filter(f => f.endsWith('.svg'));
files.forEach(f => {
    const content = fs.readFileSync(path.join(dir, f), 'utf8');
    const match = content.match(/viewBox=["']([^"']+)["']/i);
    console.log(f + ': ' + (match ? match[1] : 'No viewBox'));
});
