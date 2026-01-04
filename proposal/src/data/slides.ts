import { Slide } from '../data/slides';

export interface Slide {
  type: 'cover' | 'editorial' | 'schematic' | 'mockup' | 'impact' | 'quote' | 'comparison';
  title: string;
  subtitle?: string;
  supertitle?: string;
  company?: string;
  date?: string;
  content?: string[];
  stats?: Array<{ label: string; value: string; desc?: string }>;
  diagram?: Array<{ step: string; detail: string; icon?: string }>; 
  visual?: string; // For mockup placeholders description
  layout?: 'left' | 'right' | 'center' | 'split';
  theme?: 'dark' | 'light';
  note?: string; // For "So What" / Owner Benefit boxes
}

export const slides: Slide[] = [
  // 1. HOOK / OPENING (Enhanced)
  {
    type: 'cover',
    theme: 'dark',
    supertitle: 'CONFIDENTIAL STRATEGY',
    title: 'Bisnis hebat dibangun di atas kepercayaan antarmanusia\n dan tumbuh besar melalui sistem yang terjaga.',
    subtitle: 'Apa yang terjadi ketika orang itu pergi?',
    company: 'PT Alfa Beauty Cosmetica',
    date: 'FY 2026'
  },

  // 2. PROBLEM STATEMENT (Enhanced)
  {
    type: 'editorial',
    theme: 'light',
    layout: 'split',
    supertitle: 'CURRENT STATE',
    title: 'The Margin Leakage Crisis',
    content: [
      'Diskon diberikan tanpa policy yang jelas.\n→ Finance rekonstruksi margin manual setiap bulan.',
      'Relationship melekat ke individu.\n→ Staf resign = hubungan dengan puluhan salon reset.',
      'Operasional manual tidak scalable.\n→ Tambah 100 salon = harus tambah 2 sales.'
    ],
    stats: [
      { label: 'Active Salons', value: '3,000+' },
      { label: 'Margin Variance', value: '±15%', desc: 'Uncontrolled Fluctuation' }
    ]
  },

  // 3. STAKES (New)
  {
    type: 'schematic',
    theme: 'dark',
    supertitle: 'THE STAKES',
    title: 'Jika Cara Ini Terus Berlanjut...',
    subtitle: 'Tapi tidak harus begini.',
    diagram: [
      { step: 'Margin Tidak Visible', detail: 'Diskon diberikan. Berapa margin tersisa? Keputusan pricing tanpa data real-time.' },
      { step: 'Hubungan Bisa Reset', detail: 'Staf resign. Pesaing approach. 2 tahun trust hilang dalam sebulan.' },
      { step: 'Growth = Tambah Orang', detail: 'Tidak ada leverage teknologi. Batas tumbuh = batas rekrut.' },
    ]
  },

  // 4. VISION (New)
  {
    type: 'editorial',
    theme: 'light',
    supertitle: 'FUTURE STATE',
    title: 'Bagaimana Jika...',
    content: [
      'Setiap transaksi tercatat dengan: harga asli, diskon yang diterapkan, dan ALASAN diskon.',
      'Staf baru bisa produktif dalam sehari, karena proses sudah terstandardisasi di sistem.',
      'Relationship tetap melekat ke perusahaan, bukan ke individu yang bisa pergi kapan saja.',
      'Growth tidak lagi linear dengan jumlah tim. Teknologi menjadi multiplier, bukan replacement.'
    ],
    note: 'Inilah yang platform ini tawarkan.'
  },

  // 5. SOLUTION FRAMEWORK (Enhanced)
  {
    type: 'schematic',
    theme: 'dark',
    supertitle: 'STRATEGIC ARCHITECTURE',
    title: 'The 4 Pillars of Control',
    subtitle: 'We replace human variability with digital consistency.',
    diagram: [
      { step: '01. LOYALTY', detail: 'Lock-in via Corporate Asset.\n(Poin tidak pergi bersama staf)' },
      { step: '02. PRICING', detail: 'Algorithmic Volume Tiers.\n(Margin visible per transaksi)' },
      { step: '03. TRUST', detail: 'Verified Peer-to-Peer Social Proof.\n(Adoption tanpa marketing cost)' },
      { step: '04. SCALE', detail: 'Multi-Context Engine.\n(Semua level organisasi terlayani)' }
    ]
  },

  // 6. INTERVENTION 1: PRICING (Enhanced)
  {
    type: 'schematic',
    theme: 'light',
    supertitle: 'PILLAR 02: PRICING ENGINE',
    title: 'Algorithmic Pricing Flow',
    diagram: [
      { step: 'LOGIN', detail: 'Identify: Retail vs Contract B2B' },
      { step: 'VOLUME CHECK', detail: 'Cart Qty ≥ 12? Apply -10%' },
      { step: 'CONTRACT CHECK', detail: 'Override Base Price' },
      { step: 'INVOICE LOCK', detail: 'Snapshot Data for Audit' }
    ],
    note: 'UNTUK OWNER: Setiap diskon tercatat dengan alasannya. Margin per transaksi visible di dashboard.'
  },

  // 7. INTERVENTION 2: LOYALTY (Enhanced)
  {
    type: 'comparison',
    theme: 'light', // Changed to light to match flow or keep dark? Proposal says "Same as existing deck". Previous was 'light' for comparison.
    supertitle: 'PILLAR 01: LOYALTY',
    title: 'Shift the Asset Ownership',
    content: [ // Comparison uses content but renders it differently in component? No, SlideContent Comparison uses separate hardcoded blocks. I need to update SlideContent to use this data or keep hardcoded? 
      // SlideContent Comparison currently HARDCODES the text "Old Way" etc. I must update SlideContent to use dynamic data OR update SlideContent's hardcoded text. 
      // Strategy: I will keep the content here for reference but I MUST update SlideContent.tsx to actually read it or update the text there.
      // Wait, SlideContent.tsx Comparison implementation:
      // <p>Relationships are tied to individual sales staff...</p>
      // It is HARDCODED.
      // I must update SlideContent.tsx to support the "So What" note.
    ],
    note: 'UNTUK OWNER: Staf boleh resign. Poin dan tier loyalty tetap milik salon. Hubungan bisnis tidak reset.'
  },

  // 8. INTERVENTION 3: TRUST (Enhanced)
  {
    type: 'quote',
    theme: 'dark',
    supertitle: 'PILLAR 03: TRUST',
    title: 'Rekomendasi terbaik bagi sebuah salon adalah keberhasilan salon lainnya.',
    subtitle: 'Salon tradisional mengadopsi cara baru bukan karena marketing. Mereka percaya rekomendasi sesama.',
    content: [
      'We only allow reviews from verified transactions.',
      'Trust dibangun oleh customer sendiri.',
      'Adoption meningkat tanpa marketing spend.'
    ],
    note: 'UNTUK OWNER: Trust dibangun oleh customer sendiri. Adoption meningkat tanpa marketing spend.'
  },

  // 9. INTERVENTION 4: MULTI-CONTEXT (Reframe)
  {
    type: 'impact', // Using impact layout for the 3 blocks
    theme: 'light',
    supertitle: 'PILLAR 04: SCALE',
    title: 'Satu Platform, Semua Level',
    stats: [
      { label: 'Staf Purchasing', value: 'Easy', desc: 'Interface Bahasa Indonesia' },
      { label: 'Salon Owner', value: 'Visible', desc: 'Dashboard & Spending History' },
      { label: 'Tim Internal', value: 'Control', desc: 'Admin Panel & Reporting' }
    ],
    note: 'UNTUK OWNER: Tidak ada friction untuk adoption. Siapa pun di organisasi bisa pakai.'
  },

  // 10. PROOF / PLATFORM READY (Reframe)
  {
    type: 'schematic',
    theme: 'dark',
    supertitle: 'PROOF OF READINESS',
    title: 'Platform Sudah Siap',
    subtitle: 'Bukan mockup. Bukan prototype. Live production environment.',
    diagram: [
      { step: 'DEMO READY', detail: 'Berjalan di production. Silahkan test hari ini.' },
      { step: 'COMPLIANCE', detail: 'FEFO inventory. PPN 11% ready. Audit trail lengkap.' },
      { step: 'B2B STANDARD', detail: 'Company-level loyalty. Price snapshot. Idempotency.' },
      { step: 'RISK MITIGATED', detail: 'Auto-backup. Serverless persistence. Tested.' }
    ]
  },

  // 11. MUTUAL COMMITMENT (Reframe)
  {
    type: 'comparison', // Using comparison for 2-column layout
    theme: 'light',
    supertitle: 'NEXT STEPS',
    title: 'Langkah Bersama',
    note: 'Kami tidak minta keputusan hari ini. Kami minta kesempatan untuk membuktikan.',
    // I will need to update SlideContent.tsx Comparison to handle this specific content 
    // OR create a new slide type 'commitment'?
    // I'll stick to 'comparison' and hack the hardcoded text in SlideContent for this specific slide index or pass it via content.
    // Actually, I can use the 'content' array to pass the text for Left/Right columns.
    content: [
       'KOMITMEN KAMI: Platform siap pakai. Support selama evaluasi. Iterasi feedback.', // Left
       'DARI ANDA: 30 menit review. 5-10 salon pilot. Feedback jujur.' // Right
    ]
  },

  // 12. CLOSE
  {
    type: 'cover',
    theme: 'dark',
    supertitle: 'CONTACT',
    title: 'Terima Kasih',
    subtitle: 'Mari diskusikan pilot project ini.',
    company: 'PT Alfa Beauty Cosmetica',
    date: 'Q1 2026'
  }
];
