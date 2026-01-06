// Animations and interactions for index2.html
(function(){
  document.addEventListener('DOMContentLoaded', ()=>{
    // Simple stagger for nav buttons
    const navBtns = document.querySelectorAll('.nav button');
    navBtns.forEach((b,i)=> setTimeout(()=> b.classList.add('anim-fade','show'), 120 + i*90));

    // hero title
    const heroTitle = document.querySelector('.hero .left h1');
    if(heroTitle) setTimeout(()=> heroTitle.classList.add('anim-fade','show'), 220);

    // hero image float
    const heroImg = document.getElementById('hero-img');
    if(heroImg) setTimeout(()=> heroImg.classList.add('float'), 400);

    // reserve button microinteraction
    const rbtn = document.getElementById('reserve-btn');
    if(rbtn){
      rbtn.addEventListener('mouseenter', ()=> rbtn.animate([
        {transform:'translateY(0)'}, {transform:'translateY(-6px)'}, {transform:'translateY(0)'}
      ],{duration:420,iterations:1}));
    }

    // parallax by mousemove on hero
    const heroSection = document.querySelector('.hero');
    if(heroSection && heroImg){
      heroSection.addEventListener('mousemove', (e)=>{
        const rect = heroSection.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width - 0.5;
        const y = (e.clientY - rect.top) / rect.height - 0.5;
        heroImg.style.transform = `translate(${x * 10}px, ${y * 8}px) scale(1.01)`;
      });
      heroSection.addEventListener('mouseleave', ()=> heroImg.style.transform = 'translate(0,0)');
    }

    // store icons float
    const stores = document.querySelectorAll('.store');
    stores.forEach((s,i)=> setTimeout(()=> s.classList.add('anim-fade','show'), 400 + i*120));

    // scroll reveal for rows
    const obs = new IntersectionObserver((entries)=>{
      entries.forEach(en=>{
        if(en.isIntersecting) en.target.classList.add('show');
      });
    },{threshold:0.18});
    document.querySelectorAll('.row').forEach(r=>{ r.classList.add('anim-fade'); obs.observe(r); });

    // CTA button subtle pulse
    const cta = document.querySelector('.cta-btn');
    if(cta) cta.addEventListener('mouseenter', ()=> cta.animate([
      {transform:'scale(1)'},{transform:'scale(1.04)'},{transform:'scale(1)'}
    ],{duration:380}));
  });
})();
