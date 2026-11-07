/**
 * Konfigurasi dan Helper Jadwal & Batas Pre-order Tahu Kriuk
 * Tugas K7PPL-32 (WP-09)
 */

export const STORAGE_KEY = 'tahu_kriuk_preorder_settings'

export const initialPreorderSettings = {
  startDate: '2026-11-01',
  startTime: '08:00',
  endDate: '2026-11-30',
  endTime: '21:00',
  maxQuota: 50,
  currentOrders: 18,
  description: 'Pre-order batch tahu crispy pilihan keluarga dengan bahan segar dan kuota terbatas.',
}

/**
 * Menghitung status pre-order secara dinamis:
 * - 'Belum Dibuka' jika waktu sekarang < waktu mulai
 * - 'Ditutup' jika waktu sekarang > waktu selesai ATAU pesanan masuk >= batas kuota
 * - 'Dibuka' jika berada dalam rentang jadwal dan sisa kuota masih tersedia
 */
export function getPreorderStatus(settings, customNow = null) {
  const current = settings || initialPreorderSettings
  const now = customNow ? new Date(customNow) : new Date()

  const start = new Date(`${current.startDate}T${current.startTime || '00:00'}:00`)
  const end = new Date(`${current.endDate}T${current.endTime || '23:59'}:00`)
  const maxQuota = Math.max(0, parseInt(current.maxQuota, 10) || 0)
  const currentOrders = Math.max(0, parseInt(current.currentOrders, 10) || 0)
  const remainingQuota = Math.max(0, maxQuota - currentOrders)
  const percentFilled = maxQuota > 0 ? Math.min(100, Math.round((currentOrders / maxQuota) * 100)) : 100

  if (now < start) {
    return {
      status: 'Belum Dibuka',
      isOpen: false,
      isUpcoming: true,
      isClosed: false,
      reason: 'Jadwal pre-order belum dimulai.',
      start,
      end,
      maxQuota,
      currentOrders,
      remainingQuota,
      percentFilled,
    }
  }

  if (now > end) {
    return {
      status: 'Ditutup',
      isOpen: false,
      isUpcoming: false,
      isClosed: true,
      reason: 'Periode waktu pre-order telah berakhir.',
      start,
      end,
      maxQuota,
      currentOrders,
      remainingQuota,
      percentFilled,
    }
  }

  if (currentOrders >= maxQuota) {
    return {
      status: 'Ditutup',
      isOpen: false,
      isUpcoming: false,
      isClosed: true,
      reason: 'Batas kuota maksimal pesanan telah tercapai.',
      start,
      end,
      maxQuota,
      currentOrders,
      remainingQuota: 0,
      percentFilled: 100,
    }
  }

  return {
    status: 'Dibuka',
    isOpen: true,
    isUpcoming: false,
    isClosed: false,
    reason: 'Pre-order sedang aktif dan menerima pesanan.',
    start,
    end,
    maxQuota,
    currentOrders,
    remainingQuota,
    percentFilled,
  }
}

/**
 * Format tanggal dan jam ke gaya Indonesia yang mudah dibaca
 */
export function formatDateTimeIndo(dateStr, timeStr) {
  if (!dateStr) return '-'
  try {
    const d = new Date(`${dateStr}T${timeStr || '00:00'}:00`)
    if (Number.isNaN(d.getTime())) return `${dateStr} ${timeStr || ''}`
    return (
      d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      }) + `, pukul ${timeStr || '00:00'} WIB`
    )
  } catch {
    return `${dateStr} ${timeStr || ''}`
  }
}

/**
 * Muat konfigurasi dari localStorage jika tersedia
 */
export function loadPreorderSettings() {
  if (typeof window === 'undefined' || !window.localStorage) {
    return initialPreorderSettings
  }
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return initialPreorderSettings
    const parsed = JSON.parse(raw)
    return { ...initialPreorderSettings, ...parsed }
  } catch {
    return initialPreorderSettings
  }
}

/**
 * Simpan konfigurasi ke localStorage
 */
export function savePreorderSettings(settings) {
  if (typeof window === 'undefined' || !window.localStorage) return
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(settings))
  } catch {
    // Ignore write failures in private modes
  }
}

