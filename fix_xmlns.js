const fs = require('fs');
const path = require('path');
const dir = 'e:/Copriight/MIT-Institute-Map-System/assets/images';
const files = fs.readdirSync(dir).filter(f => f.endsWith('.svg'));
files.forEach(f => {
    let content = fs.readFileSync(path.join(dir, f), 'utf8');
    if (!content.includes('xmlns="http://www.w3.org/2000/svg"')) {
        content = content.replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" ');
        fs.writeFileSync(path.join(dir, f), content);
        console.log('Fixed ' + f);
    } else {
        console.log('Already had xmlns ' + f);
    }
});
