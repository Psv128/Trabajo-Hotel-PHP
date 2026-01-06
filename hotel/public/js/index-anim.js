// Animations and helpers for index.html
document.addEventListener('DOMContentLoaded', ()=>{
  const container = document.getElementById('main-container');
  if(!container) return;
  // show container
  setTimeout(()=> container.classList.remove('anim-hidden'), 80);
  container.classList.add('anim-show');

  // stagger children: headings, hr, pre, buttons, inputs
  const els = container.querySelectorAll('h2, hr, input, button, pre');
  els.forEach((el,i)=>{
    el.classList.add('anim-hidden');
    setTimeout(()=> el.classList.add('anim-show'), 120 + i*60);
  });

  // focus glow on inputs
  const inputs = container.querySelectorAll('input, textarea');
  inputs.forEach(inp=>{
    inp.addEventListener('focus', ()=> inp.classList.add('float-slow'));
    inp.addEventListener('blur', ()=> inp.classList.remove('float-slow'));
  });
});

// helper to pretty-print results and animate
function mostrar(d) {
  const pre = document.getElementById('resultado');
  if(!pre) return;
  pre.textContent = JSON.stringify(d, null, 2);
  pre.classList.remove('anim-hidden');
  pre.classList.add('anim-show');
}
