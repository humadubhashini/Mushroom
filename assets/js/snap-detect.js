/**
 * Snap & Detect - measures the photo's colours in the browser.
 *
 * This is the same analysis as classify_extract_features() in
 * includes/ai_classifier.php. The server uses it when PHP's GD image
 * extension is not available (common on XAMPP), so diagnosis still works.
 */
(function () {
  var input = document.getElementById('diagImage');
  var hidden = document.getElementById('browserFeatures');
  var preview = document.getElementById('diagPreview');
  if (!input || !hidden) return;

  function rgbToHsv(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    var max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min, h = 0;
    if (d > 0) {
      if (max === r) h = 60 * (((g - b) / d) % 6);
      else if (max === g) h = 60 * ((b - r) / d + 2);
      else h = 60 * ((r - g) / d + 4);
    }
    if (h < 0) h += 360;
    return [h, max > 0 ? d / max : 0, max];
  }

  function measure(img) {
    var scale = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight));
    var w = Math.max(1, Math.round(img.naturalWidth * scale));
    var h = Math.max(1, Math.round(img.naturalHeight * scale));
    var canvas = document.createElement('canvas');
    canvas.width = w; canvas.height = h;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, w, h);
    var data = ctx.getImageData(0, 0, w, h).data;

    var grid = 40, cells = [], counts = { clean: 0, green: 0, blotch: 0, dark: 0 };
    for (var gy = 0; gy < grid; gy++) {
      cells.push([]);
      for (var gx = 0; gx < grid; gx++) {
        var x = Math.min(w - 1, Math.floor((gx + 0.5) / grid * w));
        var y = Math.min(h - 1, Math.floor((gy + 0.5) / grid * h));
        var i = (y * w + x) * 4;
        var hsv = rgbToHsv(data[i], data[i + 1], data[i + 2]);
        var type = 'other';
        if (hsv[2] < 0.22) type = 'dark';
        else if (hsv[0] >= 60 && hsv[0] <= 170 && hsv[1] > 0.25) type = 'green';
        else if (hsv[0] >= 15 && hsv[0] <= 55 && hsv[1] > 0.35 && hsv[2] <= 0.85) type = 'blotch';
        else if (hsv[1] < 0.25 && hsv[2] > 0.55) type = 'clean';
        cells[gy].push(type);
        if (counts.hasOwnProperty(type)) counts[type]++;
      }
    }

    function enclosed(gy, gx) {
      var found = 0, dirs = [[0, -1], [0, 1], [-1, 0], [1, 0]];
      for (var k = 0; k < 4; k++) {
        for (var y = gy + dirs[k][0], x = gx + dirs[k][1]; y >= 0 && y < grid && x >= 0 && x < grid; y += dirs[k][0], x += dirs[k][1]) {
          if (cells[y][x] === 'clean') { found++; break; }
        }
      }
      return found === 4;
    }

    var blotchInCap = 0, darkInCap = 0;
    for (gy = 0; gy < grid; gy++) {
      for (gx = 0; gx < grid; gx++) {
        if (cells[gy][gx] === 'blotch' && enclosed(gy, gx)) blotchInCap++;
        else if (cells[gy][gx] === 'dark' && enclosed(gy, gx)) darkInCap++;
      }
    }
    var n = grid * grid, cap = Math.max(1, counts.clean + blotchInCap + darkInCap);
    return {
      clean: counts.clean / n,
      green: counts.green / n,
      blotch_in_cap: blotchInCap / cap,
      dark_in_cap: darkInCap / cap
    };
  }

  input.addEventListener('change', function () {
    hidden.value = '';
    if (!this.files || !this.files[0]) return;
    var url = URL.createObjectURL(this.files[0]);
    if (preview) { preview.src = url; preview.style.display = 'block'; }
    var img = new Image();
    img.onload = function () {
      try { hidden.value = JSON.stringify(measure(img)); } catch (e) { hidden.value = ''; }
    };
    img.src = url;
  });
})();
