import { useMemo, useState } from 'react'
import './App.css'
import products from './data/products'

const customerNavigation = [
  ['Beranda', 'home'],
  ['Produk', 'catalog'],
]

const adminNavigation = [
  ['Dashboard', 'admin-dashboard'],
  ['Varian', 'admin-variants'],
  ['Cetak Laporan', 'admin-report'],
]

const categories = ['Semua', 'Tahu Original', 'Tahu Goreng', 'Tahu Premium']

const formatCurrency = (value) =>
  value.toLocaleString('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  })

function Header({ page, isAdmin, isLoggedIn, cartCount, onNavigate, onLogout, onToggleRole }) {
  const navigation = isAdmin ? adminNavigation : customerNavigation

  return (
    <header className="topbar">
      <button
        className="brand"
        onClick={() => onNavigate(isAdmin ? 'admin-dashboard' : 'home')}
      >
        <span className="brand-mark">✦</span>
        <span>Tahu Kriuk</span>
      </button>

      <nav className="topnav" aria-label="Navigasi utama">
        {navigation.map(([label, target]) => (
          <button
            className={page === target ? 'active' : ''}
            key={target}
            onClick={() => onNavigate(target)}
          >
            {label}
          </button>
        ))}
      </nav>

      <div className="top-actions">
        {!isAdmin && isLoggedIn && (
          <button
            className="icon-button"
            aria-label="Buka keranjang"
            onClick={() => onNavigate('cart')}
          >
            🛒 <span className="cart-pill">{cartCount}</span>
          </button>
        )}
        {isAdmin ? (
          <button className="login-button" onClick={onToggleRole}>
            Keluar Admin
          </button>
        ) : isLoggedIn ? (
          <button className="login-button" onClick={onLogout}>
            Keluar
          </button>
        ) : (
          <>
            <button className="login-button" onClick={() => onNavigate('login')}>Masuk</button>
            <button className="register-button" onClick={() => onNavigate('register')}>Daftar</button>
          </>
        )}
      </div>
    </header>
  )
}

function ProductCard({ product, onAdd, onOpen, preview = false }) {
  return (
    <article className="product-card">
      <button
        className={`product-image ${preview ? 'static' : ''}`}
        onClick={() => onOpen && onOpen(product.id)}
        disabled={preview}
      >
        <img src={product.image} alt={product.name} />
        {product.badge && <span className="product-badge">{product.badge}</span>}
      </button>
      <div className="product-content">
        <span className="eyebrow">{product.category}</span>
        <h3>{product.name}</h3>
        <p>{product.description}</p>
        <div className="product-footer">
          <strong>{formatCurrency(product.price)}</strong>
          {onAdd && (
            <button className="small-button" onClick={() => onAdd(product)}>
              + Keranjang
            </button>
          )}
        </div>
      </div>
    </article>
  )
}

function HomePage({ onNavigate, onAdd, onOpen }) {
  const benefits = [
    ['◌', 'Bahan alami', 'Dibuat dari bahan pilihan'],
    ['✚', 'Higienis', 'Diproses dengan standar kebersihan tinggi'],
    ['⌁', 'Harga murah', 'Tahu kruk yang terjangkau'],
    ['☆', 'Rasa unik', 'Rasa yang berbeda dan selalu baru'],
  ]

  return (
    <>
      <section className="hero">
        <div className="hero-copy">
          <span className="eyebrow">TAHU KRIUK <i>•</i> FRESH EVERY DAY</span>
          <h1>Tahu berkualitas,<br /><em>lezat setiap hari!</em></h1>
          <p>
            Nikmati kelezatan tahu pilihan yang dibuat dari bahan berkualitas
            dan selalu segar untuk menemani setiap hidangan keluargamu.
          </p>
          <div className="hero-actions">
            <button className="primary-button" onClick={() => onNavigate('catalog')}>
              Belanja Sekarang <span>→</span>
            </button>
          </div>
          <small className="trust-note">✓ Segar, berkualitas, dan terpercaya</small>
        </div>
        <div className="hero-image">
          <img
            src="/images/tahu-super-crispy.jpg"
            alt="Tahu super crispy"
          />
          <div className="quality-tag">
            <b>✦</b>
            <span><strong>Fresh Quality</strong><small>Kualitas terbaik setiap hari</small></span>
          </div>
        </div>
      </section>

      <section className="benefit-row">
        {benefits.map(([icon, title, description]) => (
          <div className="benefit" key={title}>
            <span>{icon}</span>
            <div><b>{title}</b><small>{description}</small></div>
          </div>
        ))}
      </section>

      <section className="content-section">
        <SectionHeading
          eyebrow="Pilihan terbaik"
          title="Varian produk kami"
          description="Pilihan berkualitas dengan topping yang paling disukai."
          action={<button className="outline-button" onClick={() => onNavigate('catalog')}>Lihat Selengkapnya →</button>}
        />
        <div className="product-grid">
          {products.slice(0, 3).map((product) => (
            <ProductCard key={product.id} product={product} onAdd={onAdd} onOpen={onOpen} />
          ))}
        </div>
      </section>
    </>
  )
}

function SectionHeading({ eyebrow, title, description, action }) {
  return (
    <div className="section-heading">
      <div>
        <span className="eyebrow">{eyebrow}</span>
        <h2>{title}</h2>
        {description && <p>{description}</p>}
      </div>
      {action}
    </div>
  )
}

function CatalogPage({ onAdd, onOpen }) {
  const [category, setCategory] = useState('Semua')
  const [query, setQuery] = useState('')
  const visibleProducts = useMemo(
    () => products.filter((product) => {
      const matchesCategory = category === 'Semua' || product.category === category
      const matchesQuery = product.name.toLowerCase().includes(query.toLowerCase())
      return matchesCategory && matchesQuery
    }),
    [category, query],
  )

  return (
    <section className="content-section page-section">
      <SectionHeading
        eyebrow="Katalog produk"
        title="Semua varian tahu"
        description="Temukan produk favorit untuk kebutuhanmu."
        action={<span className="count-badge">{visibleProducts.length} Produk</span>}
      />
      <div className="catalog-tools">
        <div className="chips">
          {categories.map((item) => (
            <button
              className={category === item ? 'chip active' : 'chip'}
              key={item}
              onClick={() => setCategory(item)}
            >
              {item}
            </button>
          ))}
        </div>
        <input
          className="search-input"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="⌕  Cari produk..."
          aria-label="Cari produk"
        />
      </div>
      {visibleProducts.length ? (
        <div className="product-grid">
          {visibleProducts.map((product) => (
            <ProductCard key={product.id} product={product} onAdd={onAdd} onOpen={onOpen} />
          ))}
        </div>
      ) : (
        <EmptyState title="Produk tidak ditemukan" description="Coba gunakan kata kunci lain." />
      )}
    </section>
  )
}

function ProductDetail({ product, onAdd, onNavigate }) {
  const [quantity, setQuantity] = useState(1)

  const addProduct = () => {
    if (!onAdd(product)) return
    for (let index = 1; index < quantity; index += 1) onAdd(product)
    onNavigate('cart')
  }

  return (
    <section className="content-section page-section">
      <button className="back-button" onClick={() => onNavigate('catalog')}>← Kembali</button>
      <div className="detail-layout">
        <img className="detail-image" src={product.image} alt={product.name} />
        <div className="detail-copy">
          <span className="eyebrow">{product.category}</span>
          <h2>{product.name}</h2>
          <strong className="detail-price">{formatCurrency(product.price)}</strong>
          <p>{product.description} Dibuat setiap hari dengan bahan pilihan dan proses yang higienis.</p>
          <Field label="Deskripsi produk"><textarea readOnly value="Tahu kruk dengan bumbu balado bercita rasa gurih dan renyah." /></Field>
          <Field label="Estimasi pembuatan"><input readOnly value="1 - 2 hari" /></Field>
          <div className="quantity-line">
            <span>Jumlah pesanan</span>
            <Quantity value={quantity} onChange={setQuantity} />
          </div>
          <button className="primary-button full-button" onClick={addProduct}>Masukkan keranjang 🛒</button>
        </div>
      </div>
    </section>
  )
}

function Quantity({ value, onChange }) {
  return (
    <div className="quantity">
      <button onClick={() => onChange(Math.max(1, value - 1))}>−</button>
      <b>{value}</b>
      <button onClick={() => onChange(value + 1)}>+</button>
    </div>
  )
}

function Field({ label, children }) {
  return <label className="field">{label}{children}</label>
}

function CartPage({ cart, onUpdate, onRemove, onNavigate }) {
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0)

  return (
    <section className="content-section page-section">
      <SectionHeading eyebrow="Pesanan" title="Keranjang" />
      {cart.length ? (
        <>
          <div className="cart-list">
            {cart.map((item) => (
              <div className="cart-row" key={item.id}>
                <input type="radio" defaultChecked name="selected-product" aria-label={`Pilih ${item.name}`} />
                <img src={item.image} alt="" />
                <div><b>{item.name}</b><span>{formatCurrency(item.price)}</span></div>
                <Quantity value={item.quantity} onChange={(value) => onUpdate(item.id, value - item.quantity)} />
                <button className="remove-button" onClick={() => onRemove(item.id)}>Hapus</button>
              </div>
            ))}
          </div>
          <div className="cart-total"><span>Total pesanan</span><strong>{formatCurrency(total)}</strong></div>
          <button className="dark-button full-button" onClick={() => onNavigate('checkout')}>Bayar Sekarang</button>
        </>
      ) : (
        <EmptyState title="Keranjang masih kosong" description="Yuk, pilih produk tahu favoritmu." action={<button className="primary-button" onClick={() => onNavigate('catalog')}>Mulai Belanja</button>} />
      )}
    </section>
  )
}

function CheckoutPage({ cart, onNavigate }) {
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0)

  return (
    <section className="content-section page-section form-page">
      <SectionHeading eyebrow="Checkout" title="Informasi pembayaran" />
      <div className="checkout-list">
        {cart.map((item) => <div className="checkout-row" key={item.id}><img src={item.image} alt="" /><span>{item.name}</span><b>{formatCurrency(item.price * item.quantity)}</b><small>{item.quantity} pesanan</small></div>)}
      </div>
      <Field label="Alamat penerima"><textarea placeholder="Masukkan alamat lengkap penerima..." /></Field>
      <Field label="Pesan untuk penjual"><textarea placeholder="Tulis pesan (opsional)" /></Field>
      <div className="summary-line"><span>Total keseluruhan</span><strong>{formatCurrency(total)}</strong></div>
      <button className="yellow-button full-button" onClick={() => onNavigate('payment')}>Lanjut Pembayaran</button>
      <button className="dark-button full-button" onClick={() => onNavigate('cart')}>Kembali</button>
    </section>
  )
}

function PaymentPage({ onNavigate }) {
  return (
    <section className="content-section page-section form-page">
      <SectionHeading eyebrow="Detail pembayaran" title="Menunggu pembayaran" />
      <div className="payment-card"><h3>Pembayaran berhasil dibuat</h3><div className="qr">▦</div><p>Pindai kode QR untuk menyelesaikan pembayaran.</p></div>
      <button className="yellow-button full-button" onClick={() => onNavigate('home')}>Belum dibayar</button>
      <button className="dark-button full-button" onClick={() => onNavigate('home')}>Kembali</button>
    </section>
  )
}

function AdminPage({ page, onNavigate }) {
  if (page === 'admin-variants') {
    return <AdminVariants onNavigate={onNavigate} />
  }
  if (page === 'admin-edit') {
    return <AdminEdit onNavigate={onNavigate} />
  }
  if (page === 'admin-report') {
    return <AdminReport onNavigate={onNavigate} />
  }
  return <AdminDashboard onNavigate={onNavigate} />
}

function AdminDashboard({ onNavigate }) {
  const dates = ['27 Oktober 2026', '26 Oktober 2026', '25 Oktober 2026', '24 Oktober 2026']
  return (
    <section className="content-section page-section">
      <SectionHeading eyebrow="Halaman beranda admin" title="Pencatatan keuangan" action={<button className="outline-button" onClick={() => onNavigate('admin-report')}>Cetak laporan</button>} />
      <div className="admin-table"><div className="table-head"><b>Waktu dan tanggal</b><b>Varian</b><b>Jumlah</b><b>Total harga</b></div>{dates.map((date, index) => <div className="table-row" key={date}><span>{date}</span><span>{products[index].name}</span><span>{index + 5}</span><span>{formatCurrency((index + 5) * 5000)}</span></div>)}</div>
      <div className="stats-grid"><div><b>Persentase penjualan</b><div className="donuts"><span className="donut original">75%</span><span className="donut crispy">25%</span></div></div><div><b>Total pesanan (belum dibuat)</b><div className="metric-list"><span>Original <strong>10</strong></span><span>Balado <strong>20</strong></span><span>Keju <strong>15</strong></span></div></div></div>
    </section>
  )
}

function AdminVariants({ onNavigate }) {
  return (
    <section className="content-section page-section">
      <SectionHeading eyebrow="Manajemen varian" title="Varian tahu kruk" action={<button className="yellow-button" onClick={() => onNavigate('admin-edit')}>Tambah +</button>} />
      <div className="admin-table"><div className="table-head"><b>Nama varian</b><b>Harga</b><b>Status</b><b>Maksimal request</b><b>Edit</b></div>{products.slice(0, 3).map((product) => <div className="table-row" key={product.id}><span>{product.name}</span><span>{formatCurrency(product.price)}</span><span>Tersedia</span><span>100 pesanan</span><button onClick={() => onNavigate('admin-edit')}>Edit</button></div>)}</div>
      <h3 className="preview-title">Preview tampilan</h3>
      <div className="product-grid">{products.slice(0, 3).map((product) => <ProductCard key={product.id} product={product} preview />)}</div>
    </section>
  )
}

function AdminEdit({ onNavigate }) {
  return <section className="content-section page-section form-page narrow-form"><button className="back-button" onClick={() => onNavigate('admin-variants')}>← Kembali</button><span className="eyebrow">Edit varian</span><h2>Edit varian</h2>{['Varian', 'Harga', 'Status', 'Gambar', 'Maksimal pesanan'].map((label) => <Field label={label} key={label}><input placeholder={label === 'Gambar' ? 'Upload gambar disini' : ''} /></Field>)}<button className="dark-button" onClick={() => onNavigate('admin-variants')}>Ubah</button></section>
}

function AdminReport({ onNavigate }) {
  return <section className="content-section page-section form-page narrow-form"><button className="back-button" onClick={() => onNavigate('admin-dashboard')}>← Kembali</button><span className="eyebrow">Cetak laporan keuangan</span><h2>Pilih periode laporan</h2><div className="date-fields"><Field label="Tanggal awal"><input type="date" /></Field><Field label="Tanggal akhir"><input type="date" /></Field></div><button className="dark-button full-button" onClick={() => window.print()}>Tampilkan laporan</button></section>
}

function AuthPage({ mode, onNavigate, onSuccess, onAdminLogin }) {
  const isLogin = mode === 'login'
  const [values, setValues] = useState({ name: '', email: '', password: '', confirmPassword: '' })
  const [errors, setErrors] = useState({})
  const [submitted, setSubmitted] = useState(false)

  const updateValue = (field, value) => {
    setValues((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: '' }))
    setSubmitted(false)
  }

  const validate = () => {
    const nextErrors = {}
    if (!isLogin && values.name.trim().length < 2) nextErrors.name = 'Nama minimal 2 karakter.'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) nextErrors.email = 'Masukkan email yang valid.'
    if (values.password.length < 6) nextErrors.password = 'Password minimal 6 karakter.'
    if (!isLogin && values.confirmPassword !== values.password) nextErrors.confirmPassword = 'Konfirmasi password tidak sama.'
    return nextErrors
  }

  const submit = (event) => {
    event.preventDefault()
    const nextErrors = validate()
    setErrors(nextErrors)
    if (!Object.keys(nextErrors).length && isLogin && values.email === 'admin@tahukriuk.test' && values.password === 'admin123') {
      onAdminLogin()
      return
    }
    if (!Object.keys(nextErrors).length) {
      setSubmitted(true)
      onSuccess()
    }
  }

  return (
    <section className="auth-page">
      <div className="auth-panel">
        <span className="eyebrow">TAHU KRIUK</span>
        <h1>{isLogin ? 'Masuk' : 'Daftar'}</h1>
        <p>{isLogin ? 'Masuk untuk melanjutkan pesananmu.' : 'Buat akun untuk mulai memesan tahu favoritmu.'}</p>
        {submitted && <div className="auth-success" role="status">{isLogin ? 'Simulasi login berhasil.' : 'Simulasi pendaftaran berhasil.'}</div>}
        <form onSubmit={submit} noValidate>
          {!isLogin && <AuthField label="Nama lengkap" value={values.name} error={errors.name} onChange={(value) => updateValue('name', value)} />}
          <AuthField label="Email" type="email" value={values.email} error={errors.email} onChange={(value) => updateValue('email', value)} />
          <AuthField label="Password" type="password" value={values.password} error={errors.password} onChange={(value) => updateValue('password', value)} />
          {!isLogin && <AuthField label="Konfirmasi password" type="password" value={values.confirmPassword} error={errors.confirmPassword} onChange={(value) => updateValue('confirmPassword', value)} />}
          <button className="dark-button full-button" type="submit">{isLogin ? 'Masuk' : 'Daftar'}</button>
        </form>
        <p className="auth-alternate">
          {isLogin ? 'Belum punya akun?' : 'Sudah punya akun?'}{' '}
          <button onClick={() => onNavigate(isLogin ? 'register' : 'login')}>
            {isLogin ? 'Daftar disini' : 'Masuk disini'}
          </button>
        </p>
        <button className="back-button auth-back" onClick={() => onNavigate('home')}>← Kembali ke beranda</button>
      </div>
    </section>
  )
}

function AuthField({ label, type = 'text', value, error, onChange }) {
  return (
    <label className="field auth-field">
      {label}
      <input
        type={type}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-invalid={Boolean(error)}
      />
      {error && <small className="field-error">{error}</small>}
    </label>
  )
}

function EmptyState({ title, description, action }) {
  return <div className="empty-state"><span>⌕</span><h3>{title}</h3><p>{description}</p>{action}</div>
}

function AdminLoginPage({ onSuccess, onNavigate }) {
  const [values, setValues] = useState({ email: '', password: '' })
  const [error, setError] = useState('')

  const submit = (event) => {
    event.preventDefault()
    if (values.email !== 'admin@tahukriuk.test' || values.password !== 'admin123') {
      setError('Email atau password admin tidak sesuai.')
      return
    }
    onSuccess()
  }

  return (
    <section className="auth-page">
      <div className="auth-panel">
        <span className="eyebrow">TAHU KRIUK</span>
        <h1>Login Admin</h1>
        <p>Gunakan akun demo admin untuk membuka dashboard pengelolaan.</p>
        {error && <div className="field-error auth-feedback" role="alert">{error}</div>}
        <form onSubmit={submit} noValidate>
          <AuthField label="Email admin" type="email" value={values.email} onChange={(value) => { setValues((current) => ({ ...current, email: value })); setError('') }} />
          <AuthField label="Password" type="password" value={values.password} onChange={(value) => { setValues((current) => ({ ...current, password: value })); setError('') }} />
          <button className="dark-button full-button" type="submit">Masuk sebagai admin</button>
        </form>
        <button className="back-button auth-back" onClick={() => onNavigate('home')}>← Kembali ke beranda</button>
      </div>
    </section>
  )
}

function Footer({ onNavigate }) {
  return <footer className="footer"><div><b className="footer-brand">✦ Tahu Kriuk</b><p>Menyediakan produk tahu berkualitas, segar, dan lezat untuk keluarga Indonesia.</p></div><div><b>Navigasi</b><button onClick={() => onNavigate('home')}>Beranda</button><button onClick={() => onNavigate('catalog')}>Produk</button></div><div><b>Informasi</b><span>Bahan berkualitas</span><span>Fresh setiap hari</span><button className="admin-link" onClick={() => onNavigate('admin-login')}>Login Admin</button></div></footer>
}

function App() {
  const [page, setPage] = useState('home')
  const [cart, setCart] = useState([])
  const [isAdmin, setIsAdmin] = useState(false)
  const [isLoggedIn, setIsLoggedIn] = useState(false)
  const selectedProduct = typeof page === 'object' ? products.find((product) => product.id === page.productId) : null
  const currentPage = typeof page === 'object' ? 'detail' : page
  const cartCount = cart.reduce((total, item) => total + item.quantity, 0)

  const addToCart = (product) => {
    if (!isLoggedIn) {
      navigate('login')
      return false
    }

    setCart((items) => {
      const existing = items.find((item) => item.id === product.id)
      if (existing) return items.map((item) => item.id === product.id ? { ...item, quantity: item.quantity + 1 } : item)
      return [...items, { ...product, quantity: 1 }]
    })
    return true
  }

  const updateQuantity = (id, amount) => setCart((items) => items.map((item) => item.id === id ? { ...item, quantity: item.quantity + amount } : item).filter((item) => item.quantity > 0))
  const removeFromCart = (id) => setCart((items) => items.filter((item) => item.id !== id))
  const navigate = (target) => setPage(target)
  const openProduct = (id) => setPage({ productId: id })

  let content
  if (currentPage === 'admin-login') content = <AdminLoginPage onSuccess={() => { setIsAdmin(true); setIsLoggedIn(false); navigate('admin-dashboard') }} onNavigate={navigate} />
  else if (isAdmin) content = <AdminPage page={currentPage} onNavigate={navigate} />
  else if (currentPage === 'login' || currentPage === 'register') content = <AuthPage mode={currentPage} onNavigate={navigate} onSuccess={() => { setIsLoggedIn(true); navigate('home') }} onAdminLogin={() => { setIsAdmin(true); setIsLoggedIn(false); navigate('admin-dashboard') }} />
  else if (currentPage === 'home') content = <HomePage onNavigate={navigate} onAdd={addToCart} onOpen={openProduct} />
  else if (currentPage === 'catalog') content = <CatalogPage onAdd={addToCart} onOpen={openProduct} />
  else if (currentPage === 'detail') content = <ProductDetail product={selectedProduct || products[0]} onAdd={addToCart} onNavigate={navigate} />
  else if (currentPage === 'cart') content = <CartPage cart={cart} onUpdate={updateQuantity} onRemove={removeFromCart} onNavigate={navigate} />
  else if (currentPage === 'checkout') content = <CheckoutPage cart={cart} onNavigate={navigate} />
  else content = <PaymentPage onNavigate={navigate} />

  const toggleRole = () => {
    setIsAdmin((value) => !value)
    setPage(isAdmin ? 'home' : 'admin-dashboard')
  }

  const logout = () => {
    setIsLoggedIn(false)
    setCart([])
    navigate('home')
  }

  return <div className="app-shell"><Header page={currentPage} isAdmin={isAdmin} isLoggedIn={isLoggedIn} cartCount={cartCount} onNavigate={navigate} onLogout={logout} onToggleRole={toggleRole} /><main>{content}</main>{!isAdmin && !['login', 'register', 'admin-login'].includes(currentPage) && <Footer onNavigate={navigate} />}</div>
}

export default App
