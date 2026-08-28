document.querySelector('form')?.addEventListener('submit',e=>{e.preventDefault();localStorage.setItem('astUser',JSON.stringify({name:'Alex Morgan'}));location.href='dashboard.html'});
