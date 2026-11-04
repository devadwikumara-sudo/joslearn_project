@extends('layouts.app')

@section('content')
<h2>Form Pendataan Siswa Baru</h2>
<p class="sub">Isi data peserta didik secara lengkap pada setiap tab berikut.</p>

<div class="seg" id="tab-nav">
  <button class="tab-btn active" data-tab="A">A. Keterangan Peserta Didik</button>
  <button class="tab-btn" data-tab="B">B. Tempat Tinggal</button>
  <button class="tab-btn" data-tab="C">C. Kesehatan</button>
  <button class="tab-btn" data-tab="D">D. Pendidikan</button>
  <button class="tab-btn" data-tab="E">E. Ayah</button>
  <button class="tab-btn" data-tab="F">F. Ibu</button>
  <button class="tab-btn" data-tab="G">G. Alamat Ortu</button>
  <button class="tab-btn" data-tab="H">H. Wali</button>
  <button class="tab-btn" data-tab="I">I. Keluar/Lulus</button>
  <button class="tab-btn" data-tab="J">Lampiran</button>
  <button class="tab-btn" data-tab="K">Kenaikan</button>
</div>

<form method="POST" action="#" enctype="multipart/form-data">
  @csrf

  {{-- TAB A: Keterangan Peserta Didik --}}
  <div class="card tab-content" id="tab-A">
    <h3>A. Keterangan Peserta Didik</h3>
    <div style="display:flex;gap:20px;align-items:flex-start;">
      <div class="fl" style="flex:1;">
        <div>
          <label>Nama Lengkap</label>
          <input type="text" name="nama_lengkap" placeholder="Nama lengkap sesuai akta" />
        </div>
        <div>
          <label>Nama Panggilan</label>
          <input type="text" name="nama_panggilan" placeholder="Nama panggilan" />
        </div>
        <div>
          <label>NISN</label>
          <input type="text" name="nisn" placeholder="Nomor Induk Siswa Nasional" />
        </div>
        <div>
          <label>NIS</label>
          <input type="text" name="nis" placeholder="Nomor Induk Siswa" />
        </div>
        <div>
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin">
            <option value="">-- Pilih --</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <div>
          <label>Tempat Lahir</label>
          <input type="text" name="tempat_lahir" placeholder="Kota/Kabupaten" />
        </div>
        <div>
          <label>Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" />
        </div>
        <div>
          <label>Agama</label>
          <select name="agama">
            <option value="">-- Pilih --</option>
            <option>Islam</option>
            <option>Kristen Protestan</option>
            <option>Kristen Katolik</option>
            <option>Hindu</option>
            <option>Buddha</option>
            <option>Konghucu</option>
          </select>
        </div>
        <div>
          <label>Kewarganegaraan</label>
          <input type="text" name="kewarganegaraan" value="WNI" />
        </div>
        <div>
          <label>NIK</label>
          <input type="text" name="nik" placeholder="16 digit NIK" maxlength="16" />
        </div>
        <div>
          <label>Anak ke-</label>
          <input type="number" name="anak_ke" min="1" placeholder="1" />
        </div>
        <div>
          <label>Jumlah Saudara</label>
          <input type="number" name="jumlah_saudara" min="0" placeholder="0" />
        </div>
        <div>
          <label>Yatim/Piatu</label>
          <select name="yatim_piatu">
            <option value="">-- Pilih --</option>
            <option value="tidak">Tidak</option>
            <option value="yatim">Yatim</option>
            <option value="piatu">Piatu</option>
            <option value="yatim_piatu">Yatim Piatu</option>
          </select>
        </div>
        <div>
          <label>Bahasa Sehari-hari</label>
          <input type="text" name="bahasa" placeholder="Indonesia, Jawa, dll." />
        </div>
        <div>
          <label>Kelas</label>
          <select name="kelas">
            <option value="">-- Pilih --</option>
            <option value="X-1">X-1</option>
            <option value="XI-1">XI-1</option>
            <option value="XII-1">XII-1</option>
          </select>
        </div>
      </div>
      <div style="text-align:center;">
        <div class="ft" style="width:70px;height:92px;border:2px dashed #aaa;display:flex;align-items:center;justify-content:center;font-size:11px;color:#888;margin-bottom:6px;cursor:pointer;background:#fafafa;" onclick="document.getElementById('foto-input').click()">
          <span>Pas foto<br>3×4</span>
        </div>
        <input type="file" id="foto-input" name="foto" accept="image/*" style="display:none;" />
        <small style="color:#888;font-size:11px;">Klik untuk unggah</small>
      </div>
    </div>
    <div class="bar" style="margin-top:16px;">
      <button type="button" class="btn o" onclick="pindahTab(null, 'B')">Berikutnya</button>
      <button type="submit" class="btn a">Simpan</button>
    </div>
  </div>

  {{-- TAB B: Tempat Tinggal --}}
  <div class="card tab-content" id="tab-B" style="display:none;">
    <h3>B. Tempat Tinggal</h3>
    <div class="fl">
      <div>
        <label>Jalan / Dusun</label>
        <input type="text" name="jalan" placeholder="Nama jalan atau dusun" />
      </div>
      <div>
        <label>RT / RW</label>
        <div style="display:flex;gap:8px;">
          <input type="text" name="rt" placeholder="RT" style="width:60px;" />
          <input type="text" name="rw" placeholder="RW" style="width:60px;" />
        </div>
      </div>
      <div>
        <label>Desa / Kelurahan</label>
        <input type="text" name="desa" placeholder="Desa atau kelurahan" />
      </div>
      <div>
        <label>Kecamatan</label>
        <input type="text" name="kecamatan" placeholder="Kecamatan" />
      </div>
      <div>
        <label>Kabupaten / Kota</label>
        <input type="text" name="kabupaten" placeholder="Kabupaten atau kota" />
      </div>
      <div>
        <label>Provinsi</label>
        <input type="text" name="provinsi" placeholder="Provinsi" />
      </div>
      <div>
        <label>Kode Pos</label>
        <input type="text" name="kode_pos" placeholder="12345" maxlength="5" />
      </div>
      <div>
        <label>Nomor HP</label>
        <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" />
      </div>
      <div>
        <label>Tempat Tinggal</label>
        <select name="tempat_tinggal">
          <option value="">-- Pilih --</option>
          <option>Bersama orang tua</option>
          <option>Kos</option>
          <option>Asrama</option>
          <option>Lainnya</option>
        </select>
      </div>
      <div>
        <label>Jarak ke Sekolah (km)</label>
        <input type="number" name="jarak_km" min="0" step="0.1" placeholder="0.0" />
      </div>
    </div>
    <div class="bar" style="margin-top:16px;">
      <button type="button" class="btn o" onclick="pindahTab('A', 'C')">Sebelumnya</button>
      <button type="button" class="btn a" onclick="pindahTab('A', 'C')">Berikutnya</button>
      <button type="submit" class="btn a">Simpan</button>
    </div>
  </div>

  {{-- TAB C-K: Placeholder --}}
  @foreach(['C' => 'Kesehatan', 'D' => 'Pendidikan', 'E' => 'Ayah', 'F' => 'Ibu', 'G' => 'Alamat Ortu', 'H' => 'Wali', 'I' => 'Keluar/Lulus', 'J' => 'Lampiran', 'K' => 'Kenaikan'] as $key => $label)
  <div class="card tab-content" id="tab-{{ $key }}" style="display:none;">
    <h3>{{ ($key === 'J' ? '' : $key . '. ') }}{{ $label }}</h3>
    <p class="sub" style="color:#888;padding:24px 0;">
      Bagian ini berisi data <strong>{{ $label }}</strong>. Klik <em>Sebelumnya</em> atau <em>Berikutnya</em> untuk navigasi.
    </p>
    <div class="bar">
      @php
        $tabs = ['A','B','C','D','E','F','G','H','I','J','K'];
        $idx  = array_search($key, $tabs);
        $prev = $idx > 0 ? $tabs[$idx - 1] : null;
        $next = $idx < count($tabs) - 1 ? $tabs[$idx + 1] : null;
      @endphp
      @if($prev)
      <button type="button" class="btn o" onclick="pindahTab('{{ $prev }}', '{{ $next ?? $key }}')">Sebelumnya</button>
      @endif
      @if($next)
      <button type="button" class="btn a" onclick="pindahTab('{{ $prev ?? $key }}', '{{ $next }}')">Berikutnya</button>
      @endif
      <button type="submit" class="btn a">Simpan</button>
    </div>
  </div>
  @endforeach

</form>

<script>
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const target = this.dataset.tab;
      aktivasiTab(target);
    });
  });

  function aktivasiTab(target) {
    tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === target));
    tabContents.forEach(c => {
      c.style.display = c.id === 'tab-' + target ? '' : 'none';
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function pindahTab(prev, next) {
    if (next) aktivasiTab(next);
  }
</script>
@endsection
