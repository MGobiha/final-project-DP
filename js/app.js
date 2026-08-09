
const current = document.body.dataset.page;
document.querySelectorAll('.nav a').forEach(a=>{if(a.dataset.page===current)a.classList.add('active')});
document.querySelectorAll('[data-toggle]').forEach(t=>t.addEventListener('click',()=>t.classList.toggle('on')));
function demoToast(msg){alert(msg)}
function sendDemoSMS(){demoToast('Demo: SMS reminder queued for +94 77 123 4567. PHP will later call the SMS provider API.');}
function runEstimate(){document.getElementById('estimateResult').style.display='block'}
function sendChat(){const input=document.getElementById('chatInput');if(!input||!input.value.trim())return;const m=document.createElement('div');m.className='bubble me';m.textContent=input.value;document.querySelector('.messages').appendChild(m);const q=input.value;input.value='';setTimeout(()=>{const b=document.createElement('div');b.className='bubble bot';b.textContent='Demo response: I can answer maintenance questions and guide users through the system. The final version will connect this UI to the Python chatbot API.';document.querySelector('.messages').appendChild(b)},250)}
