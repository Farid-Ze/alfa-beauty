import { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight, Maximize2, Box, LayoutGrid } from 'lucide-react';
import { SlideContent } from './components/SlideContent';
import { slides } from './data/slides';

export default function App() {
  const [currentSlide, setCurrentSlide] = useState(0);

  const nextSlide = () => {
    if (currentSlide < slides.length - 1) {
      setCurrentSlide(currentSlide + 1);
    }
  };

  const prevSlide = () => {
    if (currentSlide > 0) {
      setCurrentSlide(currentSlide - 1);
    }
  };

  const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'ArrowRight' || e.key === 'Space') nextSlide();
    if (e.key === 'ArrowLeft') prevSlide();
  };

  useEffect(() => {
    window.addEventListener('keydown', handleKeyDown as any);
    return () => window.removeEventListener('keydown', handleKeyDown as any);
  });

  const progress = ((currentSlide + 1) / slides.length) * 100;
  const isDark = slides[currentSlide].theme === 'dark';

  return (
    <div className={`h-screen w-full flex flex-col font-body relative overflow-hidden ${isDark ? 'bg-[#0A0A0A] text-white' : 'bg-[#F7F7F7] text-[#0A0A0A]'}`}>
      
      {/* Background Texture */}
      <div className="absolute inset-0 opacity-[0.04] pointer-events-none z-0 mix-blend-multiply" style={{ backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E")` }}></div>

      {/* Header - Fixed Height 80px (h-20) */}
      <header className="relative z-20 px-6 md:px-10 lg:px-16 py-4 md:py-6 flex justify-between items-start w-full max-w-[1920px] mx-auto select-none shrink-0 h-auto md:h-20">
        <div className="flex items-center gap-4">
          <div className={`w-8 h-8 flex items-center justify-center border ${isDark ? 'border-white text-white' : 'border-black text-black'}`}>
             <Box size={16} strokeWidth={1} />
          </div>
          <div className="flex flex-col">
            <span className={`font-display text-lg tracking-tight leading-none ${isDark ? 'text-white' : 'text-black'}`}>
              ALFA BEAUTY
            </span>
            <span className="font-mono text-[9px] uppercase tracking-widest opacity-50 leading-none mt-1">
              B2B Strategic Unit
            </span>
          </div>
        </div>
        
        <div className="flex flex-col items-end gap-1">
           <div className={`px-3 py-1 border text-[9px] font-mono tracking-[0.2em] uppercase ${isDark ? 'border-white/20 text-gray-400' : 'border-black/10 text-gray-500'}`}>
             <span className="hidden md:inline">CONFIDENTIAL // </span>{slides[currentSlide].type.toUpperCase()}
           </div>
        </div>
      </header>

      {/* Main Slide Area - Takes remaining space */}
      <main className="flex-1 flex flex-col relative z-10 w-full max-w-[1920px] mx-auto overflow-hidden">
        <div className="flex-1 w-full h-full relative">
            <div className="absolute inset-0 w-full h-full">
                <SlideContent slide={slides[currentSlide]} />
            </div>
        </div>
      </main>

      {/* Footer Navigation - Fixed Height 160px (h-40) to match detailed design */}
      <footer className="relative z-20 px-6 md:px-10 lg:px-16 pb-6 md:pb-8 w-full max-w-[1920px] mx-auto select-none shrink-0 h-auto md:h-40 flex flex-col justify-end">
        {/* Top Info Row */}
        <div className="flex justify-between items-end mb-4 font-mono text-[10px] tracking-widest uppercase opacity-60 hidden md:flex">
            <span>{slides[currentSlide].company || 'PT ALFA BEAUTY COSMETICA'}</span>
            <span>{slides[currentSlide].date || 'Q1 2026'}</span>
        </div>

        {/* Progress Line */}
        <div className={`h-px w-full ${isDark ? 'bg-white/10' : 'bg-black/10'} mb-4 hidden md:block`}>
            <div 
                className="h-full bg-[#C9A962]"
                style={{ width: `${progress}%` }}
            />
        </div>

        {/* Bottom Control Row */}
        <div className="flex flex-col md:flex-row justify-between items-center relative gap-4 md:gap-0">
          
          {/* Left: Progress Stat */}
          <div className="w-full md:w-1/3 font-mono text-[10px] tracking-widest opacity-40 flex justify-between md:justify-start order-2 md:order-1">
               <span className="hidden md:inline">PROGRESS</span>
               <span className="md:ml-8 text-[#C9A962]">{Math.round(progress)}%</span>
               <span className="md:ml-8 block sm:inline mt-0">SLIDE {String(currentSlide + 1).padStart(2, '0')} / {String(slides.length).padStart(2, '0')}</span>
          </div>

          {/* Center: Navigation */}
          <div className="md:absolute md:left-1/2 md:-translate-x-1/2 flex items-center gap-8 md:gap-16 order-1 md:order-2 w-full md:w-auto justify-between md:justify-center">
            <button
              onClick={prevSlide}
              disabled={currentSlide === 0}
              className={`flex items-center gap-4 text-[10px] tracking-[0.3em] uppercase transition-opacity ${currentSlide === 0 ? 'opacity-20 cursor-not-allowed' : 'opacity-60 hover:opacity-100'} p-4 md:p-0`}
            >
              <ChevronLeft className="w-4 h-4 md:w-3 md:h-3" strokeWidth={1} />
              <span className="inline">PREV</span>
            </button>
            
            <div className={`w-px h-4 ${isDark ? 'bg-white/10' : 'bg-black/10'} hidden md:block`}></div>

            <button
              onClick={nextSlide}
              disabled={currentSlide === slides.length - 1}
              className={`flex items-center gap-4 text-[10px] tracking-[0.3em] uppercase transition-opacity ${currentSlide === slides.length - 1 ? 'opacity-20 cursor-not-allowed' : 'opacity-60 hover:opacity-100'} p-4 md:p-0`}
            >
              <span className="inline">NEXT</span>
              <ChevronRight className="w-4 h-4 md:w-3 md:h-3" strokeWidth={1} />
            </button>
          </div>

          {/* Right: Demo Link */}
          <div className="w-full md:w-1/3 flex justify-center md:justify-end order-3">
             <a href="https://app-citradesa.vercel.app" target="_blank" rel="noopener noreferrer" 
                className="opacity-50 hover:opacity-100 transition-opacity font-mono text-[10px] tracking-widest border-b border-transparent hover:border-[#C9A962] text-[#C9A962]">
                demo: app-citradesa.vercel.app
             </a>
          </div>
        </div>
      </footer>
    </div>
  );
}
