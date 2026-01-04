import { Slide } from '../data/slides';
import { ArrowRight, Check, Lock, Quote, GitCommit, MoveRight, ChevronRight, BarChart3, ShieldCheck, Box, Handshake } from 'lucide-react';

interface SlideContentProps {
  slide: Slide;
}

export function SlideContent({ slide }: SlideContentProps) {
  // THEME CONFIGURATION
  const isDark = slide.theme === 'dark';
  
  const bgClass = isDark ? 'bg-[#0A0A0A]' : 'bg-[#F7F7F7]';
  const textClass = isDark ? 'text-white' : 'text-[#0A0A0A]';
  const mutedClass = isDark ? 'text-gray-500' : 'text-gray-400';
  const borderClass = isDark ? 'border-white/10' : 'border-black/10';
  
  // CONTAINER
  const containerClass = `w-full h-full flex flex-col px-6 md:px-16 py-4 ${textClass} relative overflow-hidden select-none overflow-y-auto md:overflow-hidden slide-scrollbar`;

  // HELPER: Note Box - DISABLED (Content moved to verbal delivery)
  const NoteBox = () => {
    return null; 
  };

  // --- COMPONENT: COVER ---
  if (slide.type === 'cover') {
    return (
      <div className={`${containerClass} items-center justify-center text-center relative`}>
        
        {/* Editorial Top Anchor */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-px h-[12vh] bg-gradient-to-b from-[#C9A962] to-transparent opacity-50" />

        {/* Main Content Group */}
        <div className="flex flex-col items-center justify-center flex-1 w-full z-10 pt-[5vh]">
            
            {/* Supertitle Group */}
            <div className="flex items-center gap-5 mb-[3vh] opacity-80">
                <div className="h-px w-10 bg-[#C9A962]"></div>
                <p className="font-mono text-[10px] tracking-[0.4em] uppercase text-[#C9A962]">
                    {slide.supertitle}
                </p>
                <div className="h-px w-10 bg-[#C9A962]"></div>
            </div>

            {/* Title */}
            <h1 className="font-display leading-[0.9] -tracking-[0.03em] text-center text-balance px-4 text-[3.25rem] sm:text-[5rem] md:text-[6rem] lg:text-[clamp(3rem,13vh,8.5rem)]">
                {slide.title}
            </h1>

        </div>
      </div>
    );
  }

  // --- COMPONENT: EDITORIAL ---
  if (slide.type === 'editorial') {
    return (
      <div className={containerClass}>
        {/* Header Section - Standardized Flex Layout */}
        <div className="flex justify-between items-end border-b border-[#C9A962]/30 pb-4 mb-[4vh] shrink-0">
          <div>
            <span className="font-mono text-[10px] text-[#C9A962] mb-2 block tracking-widest uppercase">{slide.supertitle || 'INSIGHT'}</span>
            <h2 className="font-display" style={{ fontSize: 'clamp(2rem, 6vh, 4.5rem)' }}>{slide.title}</h2>
          </div>
          <div className="flex items-center gap-2 text-[#C9A962] px-3 py-1.5 bg-[#C9A962]/5 rounded-full">
            <span className="font-mono text-[9px] uppercase tracking-widest">Analysis</span>
            <BarChart3 size={12} />
          </div>
        </div>

        {/* Content - Split Layout */}
        <div className="flex-1 min-h-0 grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-16 relative overflow-y-auto md:overflow-visible pb-8 md:pb-0">
            
            {/* Left Column: Stats/Key Metrics */}
            <div className="flex flex-col justify-center border-b md:border-b-0 md:border-r border-[#C9A962]/10 pb-8 md:pb-0 md:pr-12">
               {slide.stats ? (
                 <div className="flex flex-col gap-8 md:gap-12">
                   {slide.stats.map((stat, idx) => (
                     <div key={idx} className="group">
                       <div className="flex items-baseline gap-4 mb-2">
                         <span className="font-display leading-none text-[#0A0A0A] dark:text-[#E5E5E5]"
                               style={{ fontSize: 'clamp(2.5rem, 8vh, 6rem)' }}>
                               {stat.value}
                         </span>
                       </div>
                       <div className="w-12 h-px bg-[#C9A962] mb-3 opacity-50 group-hover:w-full transition-all duration-700"></div>
                       <span className="block font-mono text-[10px] uppercase tracking-[0.2em] text-[#C9A962] mb-1">{stat.label}</span>
                       {stat.desc && <span className="block font-body text-[10px] text-gray-500 tracking-wide">{stat.desc}</span>}
                     </div>
                   ))}
                 </div>
               ) : (
                 <div className="h-full flex items-center justify-center opacity-20 py-10 md:py-0">
                    <Quote size={80} className="text-[#C9A962] md:w-[100px] md:h-[100px]" />
                 </div>
               )}
            </div>

            {/* Right Column: Narrative */}
            <div className="flex flex-col justify-center md:pl-4 pt-8 md:pt-0">
               <div className="space-y-6 md:space-y-10">
                  {slide.content?.map((paragraph, idx) => (
                  <div key={idx} className="relative pl-8 group">
                      <div className="absolute left-0 top-2 w-1.5 h-1.5 rounded-full border border-[#C9A962] group-hover:bg-[#C9A962] transition-colors duration-300"></div>
                      {idx < (slide.content?.length || 0) - 1 && (
                        <div className="absolute left-[3px] top-6 bottom-[-24px] w-px bg-[#C9A962]/20"></div>
                      )}
                      
                      <p className={`font-body leading-relaxed whitespace-pre-line text-lg ${idx === 0 ? 'text-[#0A0A0A] dark:text-[#E5E5E5]' : 'text-gray-500'}`}
                          style={{ fontSize: 'clamp(1rem, 2.2vh, 1.4rem)' }}>
                          {paragraph}
                      </p>
                  </div>
                  ))}
               </div>
            </div>

        </div>
        <NoteBox />
      </div>
    );
  }

  // --- COMPONENT: SCHEMATIC ---
  if (slide.type === 'schematic') {
    return (
      <div className={containerClass}>
        {/* Header Section */}
        <div className="flex justify-between items-end border-b border-[#C9A962]/30 pb-4 mb-[4vh] shrink-0">
          <div>
            <span className="font-mono text-[10px] text-[#C9A962] mb-2 block tracking-widest uppercase">{slide.supertitle}</span>
            <h2 className="font-display" style={{ fontSize: 'clamp(2rem, 6vh, 4.5rem)' }}>{slide.title}</h2>
          </div>
          <div className="flex items-center gap-2 text-[#C9A962] px-3 py-1.5 bg-[#C9A962]/5 rounded-full">
            <span className="font-mono text-[9px] uppercase tracking-widest">Process Flow</span>
            <GitCommit size={12} />
          </div>
        </div>

        {/* Diagram Container */}
        <div className="flex-1 flex flex-col justify-center relative w-full min-h-0 overflow-y-auto md:overflow-visible">
          <div className={`absolute top-1/2 left-0 w-full h-px ${borderClass} -translate-y-1/2 z-0 hidden md:block`}></div>
          <div className={`absolute left-4 top-4 bottom-4 w-px ${borderClass} z-0 md:hidden`}></div>
          
          <div className="flex flex-col md:flex-row w-full h-full justify-start md:justify-center items-center relative z-10 py-8 md:py-0 gap-8 lg:gap-0">
            {slide.diagram?.map((item, idx) => (
              <div key={idx} className="relative flex flex-row md:flex-col h-auto md:h-full justify-start md:justify-center group pl-12 md:pl-6 md:px-6 w-full md:w-1/4">
                {/* Connector Dot */}
                <div className="absolute left-[13px] top-0 md:top-1/2 md:left-1/2 -translate-x-1/2 md:-translate-y-1/2 z-10">
                   <div className={`w-3 h-3 bg-[#0A0A0A] border ${idx === 0 ? 'border-[#C9A962]' : 'border-white/10'} rounded-full flex items-center justify-center transition-colors duration-500 group-hover:border-[#C9A962]/50`}>
                      <div className={`w-1 h-1 rounded-full ${idx === 0 ? 'bg-[#C9A962]' : 'bg-transparent group-hover:bg-[#C9A962]/50'}`}></div>
                   </div>
                </div>

                <div className="flex flex-col w-full">
                    {/* Top Half (Desktop) / Title (Mobile) */}
                    <div className="flex-none md:flex-1 flex flex-col justify-start md:justify-end items-start md:items-center pb-2 md:pb-10 text-left md:text-center">
                       <span className="font-mono text-[#C9A962] text-[11px] mb-2 md:mb-4 block tracking-[0.25em] opacity-60 transition-opacity group-hover:opacity-100">0{idx + 1}</span>
                       <h3 className="font-display leading-none break-words w-full md:text-balance" style={{ fontSize: 'clamp(1.25rem, 4vh, 3rem)' }}>{item.step}</h3>
                    </div>

                    {/* Bottom Half (Desktop) / Detail (Mobile) */}
                    <div className="flex-none md:flex-1 flex flex-col justify-start items-start md:items-center pt-2 md:pt-10 text-left md:text-center">
                       <div className="w-12 h-px md:w-px md:h-12 mb-2 md:mb-5 bg-[#C9A962]/20 transition-colors group-hover:bg-[#C9A962]/40 hidden md:block"></div>
                       <p className={`font-body text-[10px] md:text-[11px] leading-relaxed ${mutedClass} uppercase tracking-[0.15em] w-full whitespace-pre-line opacity-50 transition-opacity group-hover:opacity-100`}>{item.detail}</p>
                    </div>
                </div>
                
                {idx < (slide.diagram?.length || 0) - 1 && (
                  <div className="absolute left-[16.5px] top-[20px] bottom-[-20px] w-px border-l border-dashed border-[#C9A962]/20 md:hidden"></div>
                )}
                {idx < (slide.diagram?.length || 0) - 1 && (
                  <div className="absolute top-1/2 right-0 -translate-x-1/2 -translate-y-1/2 z-20 text-[#C9A962] opacity-10 hidden md:block">
                    <ChevronRight size={16} />
                  </div>
                )}
              </div>
            ))}
          </div>
          
          <div className="absolute -left-4 top-1/2 -translate-y-1/2 text-[#C9A962] opacity-40 font-mono text-[9px] -rotate-90 hidden md:block">INPUT</div>
          <div className="absolute -right-4 top-1/2 -translate-y-1/2 text-[#C9A962] opacity-40 font-mono text-[9px] -rotate-90 hidden md:block">OUTPUT</div>
        </div>

        {/* Footer Note */}
        <NoteBox />
      </div>
    );
  }

  // --- COMPONENT: MOCKUP ---
  if (slide.type === 'mockup') {
    return (
      <div className={containerClass}>
        <div className="flex flex-col md:flex-row justify-between items-start md:items-end mb-[3vh] border-b border-white/5 pb-4 shrink-0 gap-4 md:gap-0">
          <div>
            <span className="font-mono text-[10px] text-[#C9A962] mb-2 block tracking-widest uppercase">{slide.supertitle}</span>
            <h2 className="font-display" style={{ fontSize: 'clamp(2rem, 5vh, 4rem)' }}>{slide.title}</h2>
          </div>
          <div className="flex flex-wrap gap-4 md:gap-6">
            {slide.content?.map((txt, i) => (
              <div key={i} className="flex items-center gap-2">
                 <div className="w-1 h-1 bg-[#C9A962] rounded-full"></div>
                 <p className={`font-body text-[9px] uppercase tracking-widest ${mutedClass}`}>{txt}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Browser Window - Responsive Height */}
        <div className="flex-1 min-h-[300px] border border-[#C9A962]/20 p-2 relative bg-[#F7F7F7] w-full shadow-2xl rounded-sm overflow-hidden flex flex-col">
           {/* Chrome */}
           <div className="h-8 bg-[#E5E5E5] border-b border-[#D4D4D4] flex items-center px-3 gap-2 shrink-0">
              <div className="flex gap-1.5">
                 <div className="w-2.5 h-2.5 rounded-full bg-[#FF5F57] border border-[#E0443E]"></div>
                 <div className="w-2.5 h-2.5 rounded-full bg-[#FEBC2E] border border-[#D89E24]"></div>
                 <div className="w-2.5 h-2.5 rounded-full bg-[#28C840] border border-[#1AAB29]"></div>
              </div>
              <div className="flex-1 flex justify-center">
                 <div className="bg-white rounded-md px-3 py-0.5 flex items-center gap-2 text-[9px] font-mono text-gray-500 shadow-sm border border-gray-200 w-full md:w-1/2 justify-center truncate">
                    <Lock size={8} className="text-green-600 shrink-0" /> 
                    <span className="text-black truncate">partner.alfabeauty.com/store</span>
                 </div>
              </div>
           </div>

           {/* Viewport */}
           <div className="flex-1 bg-white flex relative overflow-hidden">
              <div className="w-16 md:w-48 border-r border-gray-100 bg-gray-50 p-2 md:p-4 flex flex-col gap-4 shrink-0 items-center md:items-stretch">
                 <div className="h-6 w-6 bg-[#0A0A0A] rounded-full flex items-center justify-center text-white font-serif text-[10px]">A</div>
                 <div className="space-y-2 hidden md:block">
                    <div className="h-1.5 w-full bg-gray-200 rounded-full"></div>
                    <div className="h-1.5 w-3/4 bg-gray-200 rounded-full"></div>
                 </div>
                 <div className="mt-auto p-1 md:p-3 bg-white border border-gray-200 shadow-sm rounded-lg flex flex-col items-center md:items-start">
                    <div className="text-[9px] uppercase tracking-wider text-gray-400 mb-1 hidden md:block">Assets</div>
                    <div className="text-[10px] md:text-lg font-display text-[#C9A962]">12K</div>
                 </div>
              </div>
              
              <div className="flex-1 flex flex-col bg-white min-w-0">
                 <div className="h-14 border-b border-gray-100 flex items-center justify-between px-4 md:px-6 bg-white/80 shrink-0">
                    <div className="font-display text-lg md:text-xl">Catalog</div>
                    <div className="h-6 w-6 bg-[#C9A962] text-white flex items-center justify-center text-[10px] rounded-full">AB</div>
                 </div>
                 
                 <div className="p-4 md:p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 overflow-y-auto min-h-0">
                    <div className="border border-gray-200 bg-white p-3">
                       <div className="aspect-[4/3] bg-gray-100 mb-3"></div>
                       <div className="h-2 w-2/3 bg-gray-800 mb-2"></div>
                       <div className="h-2 w-1/3 bg-[#C9A962]"></div>
                    </div>
                    <div className="border-2 border-[#C9A962] bg-white p-3 shadow-lg relative">
                       <div className="absolute top-0 right-0 bg-[#C9A962] text-black text-[8px] font-bold px-2 py-0.5">TOP</div>
                       <div className="aspect-[4/3] bg-gray-50 mb-3 p-2 flex items-center justify-center">
                          <div className="w-1/2 h-2/3 bg-gray-200"></div>
                       </div>
                       <div className="h-2 w-3/4 bg-black mb-2"></div>
                       <div className="h-2 w-1/2 bg-[#C9A962]"></div>
                    </div>
                 </div>
              </div>
           </div>

           {/* Annotation - Positioned absolutely but constrained */}
           <div className="absolute bottom-4 right-4 bg-[#0A0A0A] text-white p-4 max-w-[200px] border-t-2 border-[#C9A962] shadow-xl z-20 hidden md:block">
              <div className="flex items-center gap-2 mb-2 text-[#C9A962]">
                 <GitCommit size={12} />
                 <span className="font-mono text-[8px] uppercase tracking-widest">Logic</span>
              </div>
              <p className="font-body text-[10px] leading-relaxed text-gray-300 line-clamp-3">{slide.visual}</p>
           </div>
        </div>
        <NoteBox />
      </div>
    );
  }

  // --- COMPONENT: IMPACT ---
  if (slide.type === 'impact') {
    return (
      <div className={containerClass}>
        <div className="text-center mb-6 md:mb-[5vh] shrink-0 pt-6 md:pt-0">
          <span className="font-mono text-[10px] text-[#C9A962] tracking-[0.4em] uppercase block mb-3 md:mb-4">{slide.supertitle}</span>
          <h2 className="font-display tracking-tight leading-[1.05] text-[#0A0A0A] dark:text-[#E5E5E5] px-4" 
              style={{ fontSize: 'clamp(2.15rem, 5vh, 6rem)' }}>
              {slide.title}
          </h2>
        </div>

        <div className="flex-1 flex flex-col justify-start md:justify-center min-h-0 pb-8 overflow-y-auto md:overflow-hidden w-full items-center">
           <div className="flex flex-col md:grid md:grid-cols-3 w-full max-w-5xl mx-auto border border-[#C9A962]/30 shadow-sm md:h-full md:max-h-[50vh]">
            {slide.stats?.map((stat, idx) => (
               <div key={idx} className={`
                  flex flex-col items-center justify-center text-center relative
                  py-8 px-4 md:p-8
                  border-b md:border-b-0 md:border-r border-[#C9A962]/30 
                  last:border-b-0 last:border-r-0
                  bg-[#FAF9F6] dark:bg-white/5
                  md:h-full
               `}>
                 <span className="font-mono text-[9px] uppercase tracking-[0.2em] mb-4 text-gray-400 dark:text-gray-500">
                    {stat.label}
                 </span>
                 
                 <span className="font-display tracking-tight text-[#C9A962] leading-none mb-5 drop-shadow-sm"
                       style={{ fontSize: 'clamp(2.75rem, 5vh, 5rem)' }}> 
                       {stat.value}
                 </span>
                 
                 <div className={`inline-flex items-center justify-center gap-2 border border-[#C9A962]/20 px-4 py-1.5 rounded-full bg-[#C9A962]/5 max-w-full shadow-sm`}>
                    {idx === 0 && <BarChart3 className="text-[#C9A962] w-3 h-3 shrink-0" />}
                    <span className={`font-mono text-[8px] md:text-[9px] uppercase tracking-widest truncate ${isDark ? 'text-[#C9A962]' : 'text-gray-600'}`}>
                        {stat.desc}
                    </span>
                 </div>
               </div>
            ))}
           </div>
        </div>
        <NoteBox />
      </div>
    );
  }

  // --- COMPONENT: QUOTE ---
  if (slide.type === 'quote') {
    return (
      <div className={containerClass}>
        {/* Header - Absolute & Floating */}
        <div className="absolute top-0 left-0 right-0 z-20 px-6 md:px-8 pt-4 md:pt-6 pb-2">
           <div className="flex items-end justify-between border-b border-[#C9A962]/30 pb-4">
               <div>
                  <span className="font-mono text-[10px] text-[#C9A962] tracking-[0.2em] uppercase block mb-3 opacity-80">{slide.supertitle}</span>
               </div>
               <div className="flex items-center gap-3 text-[#C9A962] opacity-60">
                  <span className="font-mono text-[9px] uppercase tracking-widest hidden md:inline">CONFIDENTIAL // QUOTE</span>
                  <Quote size={14} />
               </div>
           </div>
        </div>

        {/* Content - Centered */}
        <div className="flex-1 min-h-0 flex flex-col justify-center items-center pt-32 md:pt-[10vh] relative z-10 text-center px-0 md:px-4 overflow-y-auto">
            
            {/* Main Quote */}
            <div className="mb-10 relative w-full max-w-6xl">
               <Quote className="text-[#C9A962] opacity-[0.03] absolute -top-8 md:-top-16 -left-4 md:-left-16 w-20 h-20 md:w-32 md:h-32" />
               <h2 className="font-display leading-[0.9] relative z-10 tracking-tight text-balance text-[2.5rem] sm:text-[3.5rem] md:text-[5rem] lg:text-[clamp(3rem,7vh,5.5rem)]">
                 {slide.title}
               </h2>
               <Quote className="text-[#C9A962] opacity-[0.03] absolute -bottom-8 md:-bottom-16 -right-4 md:-right-16 rotate-180 w-20 h-20 md:w-32 md:h-32" />
            </div>

            {/* Separator */}
            <div className="w-12 h-1 bg-[#C9A962] mb-10 rounded-full opacity-60" />

            {/* Subtitle */}
            <p className={`font-body ${mutedClass} max-w-3xl leading-relaxed mb-16 opacity-80`}
               style={{ fontSize: 'clamp(1rem, 2.2vh, 1.4rem)' }}>
               {slide.subtitle}
            </p>

            {/* Bullet Points */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-left max-w-5xl w-full border-t border-[#C9A962]/10 pt-10 pb-8">
               {slide.content?.map((txt, i) => (
                 <div key={i} className="flex gap-4 items-start group">
                    <div className="w-4 h-4 rounded-full border border-[#C9A962] flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-[#C9A962] transition-colors duration-300">
                       <Check className="text-[#C9A962] group-hover:text-black w-2.5 h-2.5 transition-colors duration-300" />
                    </div>
                    <p className="font-body font-light leading-relaxed text-gray-400 group-hover:text-[#C9A962] transition-colors duration-300 text-[10px] uppercase tracking-widest">
                       {txt}
                    </p>
                 </div>
               ))}
            </div>

        </div>
        <NoteBox />
      </div>
    );
  }

  // --- COMPONENT: COMPARISON ---
  if (slide.type === 'comparison') {
    const isCommitmentSlide = slide.title === 'Langkah Bersama';
    
    return (
      <div className={containerClass}>
        {/* Header Section - Standardized Flex Layout */}
        <div className="flex justify-between items-end border-b border-[#C9A962]/30 pb-4 mb-[4vh] shrink-0">
          <div>
            <span className="font-mono text-[10px] text-[#C9A962] mb-2 block tracking-widest uppercase">{slide.supertitle}</span>
            <h2 className="font-display" style={{ fontSize: 'clamp(2rem, 6vh, 4.5rem)' }}>{slide.title}</h2>
          </div>
          <div className="flex items-center gap-2 text-[#C9A962] px-3 py-1.5 bg-[#C9A962]/5 rounded-full">
            <span className="font-mono text-[9px] uppercase tracking-widest hidden md:inline">{isCommitmentSlide ? 'Partnership' : 'Transformation'}</span>
            {isCommitmentSlide ? <Handshake size={12} /> : <MoveRight size={12} />}
          </div>
        </div>

        <div className="flex-1 min-h-0 grid grid-cols-1 md:grid-cols-2 relative gap-px overflow-y-auto md:overflow-hidden">
           <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-[#C9A962] rounded-full hidden md:flex items-center justify-center border-4 border-[#0A0A0A]">
              {isCommitmentSlide ? <Handshake className="text-[#0A0A0A] w-4 h-4" /> : <ArrowRight className="text-[#0A0A0A] w-4 h-4" />}
           </div>

           {/* Left Block */}
           <div className="bg-[#1A1A1A] md:border-r border-[#C9A962]/10 p-8 lg:p-12 flex flex-col relative text-gray-400 justify-center">
              {isCommitmentSlide ? (
                 // COMMITMENT LEFT
                 <>
                    <span className="font-mono text-[10px] uppercase tracking-widest opacity-50 mb-8 relative z-10 flex items-center gap-2">
                         <div className="w-1 h-1 rounded-full bg-current"></div> KOMITMEN KAMI
                    </span>
                    <div className="relative z-10">
                        <div className="space-y-5">
                            {(slide.content?.[0] || '').split('. ').map((item, i) => (
                                item && (
                                    <div key={i} className="flex gap-4 items-start group">
                                        <Check size={14} className="text-[#C9A962] shrink-0 mt-1 opacity-50 group-hover:opacity-100 transition-opacity" />
                                        <p className="font-body text-gray-400 text-sm leading-relaxed">{item.trim()}.</p>
                                    </div>
                                )
                            ))}
                        </div>
                    </div>
                 </>
              ) : (
                 // STANDARD LEFT
                 <>
                    <span className="font-mono text-[10px] uppercase tracking-widest opacity-50 mb-8 relative z-10 flex items-center gap-2">
                         <div className="w-1 h-1 rounded-full bg-red-500/50"></div> Current State
                    </span>
                    <div className="relative z-10 opacity-60 hover:opacity-100 transition-opacity duration-500">
                        <h3 className="font-display text-gray-500 line-through decoration-red-500/30 decoration-1 mb-4 text-[2rem] md:text-[clamp(2rem,5vh,3.5rem)]">
                            Personal User
                        </h3>
                        <p className="font-body leading-relaxed max-w-md font-light mb-6 text-[1rem] md:text-[clamp(1rem,2vh,1.25rem)]">
                            Relationships are tied to individual sales staff. Data is fragmented.
                        </p>
                        <div className="inline-flex items-center gap-2 text-red-400/80 font-mono text-[9px] uppercase tracking-wider border border-red-900/30 px-2 py-1 rounded">
                            <span className="w-1.5 h-1.5 bg-red-500/50 rounded-full"></span> Risk: High Churn
                        </div>
                    </div>
                 </>
              )}
           </div>

           {/* Right Block */}
           <div className="bg-[#0A0A0A] p-8 lg:p-12 flex flex-col relative text-[#E5E5E5] justify-center">
              {isCommitmentSlide ? (
                 // COMMITMENT RIGHT
                 <>
                    <span className="font-mono text-[10px] uppercase tracking-widest text-[#C9A962] mb-8 relative z-10 flex items-center gap-2">
                         <div className="w-1 h-1 rounded-full bg-[#C9A962]"></div> DARI ANDA
                    </span>
                    <div className="relative z-10">
                        <div className="space-y-5">
                            {(slide.content?.[1] || '').split('. ').map((item, i) => (
                                item && (
                                    <div key={i} className="flex gap-4 items-start">
                                        <span className="font-mono text-[10px] text-[#C9A962] shrink-0 mt-0.5">0{i+1}</span>
                                        <p className="font-body text-gray-300 text-sm leading-relaxed">{item.trim()}.</p>
                                    </div>
                                )
                            ))}
                        </div>
                    </div>
                 </>
              ) : (
                 // STANDARD RIGHT
                 <>
                    <span className="font-mono text-[10px] uppercase tracking-widest text-[#C9A962] mb-8 relative z-10 flex items-center gap-2">
                         <div className="w-1 h-1 rounded-full bg-[#C9A962]"></div> Future State
                    </span>
                    <div className="relative z-10">
                        <h3 className="font-display text-[#E5E5E5] mb-4 text-[2.5rem] md:text-[clamp(2.5rem,6vh,4.5rem)]">
                            Corporate Entity
                        </h3>
                        <div className="border-l-2 border-[#C9A962] pl-6 py-1 mb-6">
                            <p className="font-body leading-relaxed text-gray-300 font-light text-[1rem] md:text-[clamp(1.1rem,2.2vh,1.5rem)]">
                            Value is accrued by the Salon Business itself.
                            </p>
                        </div>
                        <div className="inline-flex items-center gap-2 text-[#C9A962] font-mono text-[9px] uppercase tracking-wider border border-[#C9A962]/30 px-2 py-1 rounded">
                            <ShieldCheck size={12} /> Asset Protection
                        </div>
                    </div>
                 </>
              )}
           </div>
        </div>
        <NoteBox />
      </div>
    );
  }

  return null;
}
