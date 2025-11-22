# Kompressor

### A lightweight Laravel image compression library

Kompressor is a Laravel-ready package that compresses uploaded images using **Intervention Image** and **Spatie Image Optimizer**, with automatic fallback between `imagick` and `gd`. The system will prefer `imagic`. If it is not installed, the system will attempt to install it. If installation fails, it will fallback to `gd`. 

It provides an easy, fluent API:

```php
$compressed = Kompressor::compress($request->file('image'));
```

---

## Features

* Automatic driver selection (`imagick → gd`)
* Compress images to a target max size (KB)
* Optimizes using external binaries when available
* Stores original + compressed versions
* Laravel Storage-ready
* Zero configuration required (auto-publishes config)

---

# Installation

### Install via Composer

```bash
composer require jessyledama/kompressor
```

---

# Dependencies

Kompressor depends on these packages:

### Laravel packages (installed automatically)

| Package                    | Purpose                         |
| -------------------------- | ------------------------------- |
| **intervention/image**     | Image manipulation (GD/Imagick) |
| **spatie/image-optimizer** | Lossless optimization           |

These will be installed automatically. If they are not, run:

```bash
composer require intervention/image spatie/image-optimizer
```

---

# System Dependencies

Spatie/ImageOptimizer uses external binaries (**optional** but **highly recommended**) for best compression speed & quality.

### Ubuntu / Debian

```bash
sudo apt install jpegoptim optipng pngquant gifsicle webp
```

### CentOS / AlmaLinux

```bash
sudo yum install epel-release
sudo yum install jpegoptim optipng pngquant gifsicle libwebp-tools
```

### macOS (Homebrew)

```bash
brew install jpegoptim optipng pngquant gifsicle webp
```

If these tools are missing, Kompressor still works (slower, purely PHP).

---

# Publish Config (optional)

If you want to customize the directories or max allowed size:

```bash
php artisan vendor:publish --tag=kompressor-config
```

This creates:

```
config/kompressor.php
```

Example:

```php
return [
    'original_path'   => 'original-images',
    'compressed_path' => 'compressed-images',
    'max_kb'          => 300, // target size in KB
];
```

---

# Usage

### Basic Example (Controller)

```php
use Kompressor;
use Illuminate\Http\Request;

public function store(Request $request)
{
    $validated = $request->validate([
        'image' => ['required', 'mimes:jpg,jpeg,png,gif'],
    ]);

    $compressed = Kompressor::compress($request->file('image'));

    // Example return:
    [
        "original" => "original-images/abc123.jpg",
        "compressed" => "compressed-images/compressed_abc123.jpg",
        "final_size_kb" => 152,
        "driver_used" => "imagick",
        "compression_time_seconds" => $elapsed
    ]

    $path = $compressed['compressed'];

    // Save to DB, etc...
}
```

---

# Returned Data Structure

Kompressor returns an array:

```php
[
    'original'       => 'original-images/filename.jpg',
    'compressed'     => 'compressed-images/compressed_filename.jpg',
    'final_size_kb'  => 120.45,
    'driver_used'    => 'imagick',
    'compression_time_seconds' => $elapsed
]
```

---

# Using in Blade

```blade
<img src="{{ asset('storage/' . $compressed['compressed']) }}">
```

---

# Custom Max Size

Edit `config/kompressor.php`:

```php
'max_kb' => 200,
```

---

# Driver Fallback Logic

Kompressor checks in order:

| State                 | Action                            |
| --------------------- | --------------------------------- |
| **imagick installed** | Uses Imagick (fastest & cleanest) |
| **imagick missing**   | Logs warning + uses GD            |

---

# Development Instructions

Clone the repo:

```bash
git clone https://github.com/jessyledama/kompressor.git
```

Install dependencies:

```bash
composer install
```

---

# License

This package is open-source software licensed under the **MIT License**.

---

# Contributions

Pull requests and issues are welcome!
