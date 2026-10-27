const toggle = document.querySelector('.menu-toggle');
const mobile = document.querySelector('.mobile-nav');
if(toggle && mobile){ toggle.addEventListener('click',()=>{const open=mobile.classList.toggle('open');toggle.setAttribute('aria-expanded',String(open));toggle.textContent=open?'✕':'☰';}); }
