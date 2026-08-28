document.querySelectorAll('[data-news-filter]').forEach(b=>b.addEventListener('click',()=>alert(`Showing ${b.dataset.newsFilter} news`)));
