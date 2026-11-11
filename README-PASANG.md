# File JosBook untuk Laravel

Folder ini adalah overlay. Buat project Laravel terlebih dahulu dengan Composer, lalu gabungkan isi folder ini ke project tersebut.

Tampilan prototype disertakan pada `resources/views/josbook.blade.php`, `public/css/josbook.css`, dan `public/js/josbook.js`.

Tambahkan route ini ke `routes/web.php` Laravel:
```php
use Illuminate\Support\Facades\Route;
Route::view('/', 'josbook')->name('josbook.home');
```
Jika route `/` sudah ada, jangan membuat route duplikat. Controller, model, dan service yang tersedia masih kerangka awal.
