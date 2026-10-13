// wired-item 3.0.0-rc.6 selalu gagal menggambar: fungsi hachureFill bawaan
// library memanggil `fillPolygon` yang sudah tidak ada di roughjs 4. Error itu
// membatalkan render, sehingga item tetap tersembunyi (opacity: 0) dan isi
// dropdown tidak pernah terlihat. Gambar bawaan wired-item hanya dipakai untuk
// penanda item terpilih, jadi aman dimatikan supaya itemnya bisa tampil.
import 'wired-elements/lib/wired-item.js';

const wiredItemClass = customElements.get('wired-item');

if (wiredItemClass) {
    wiredItemClass.prototype.draw = () => {};
}
