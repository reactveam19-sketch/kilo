const loadMoreButton = document.querySelector('[data-load-more]');

if (loadMoreButton) {
    loadMoreButton.addEventListener('click', async () => {
        const offset = Number(loadMoreButton.dataset.offset || 0);
        const response = await fetch(`/api/load_more.php?offset=${offset}`);
        if (!response.ok) {
            return;
        }
        const data = await response.json();
        const grid = document.querySelector('[data-article-grid]');
        grid.insertAdjacentHTML('beforeend', data.html);
        loadMoreButton.dataset.offset = offset + data.count;
        if (!data.has_more) {
            loadMoreButton.remove();
        }
    });
}
