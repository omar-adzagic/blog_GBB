function updatePagination(total, page, limit) {
    const totalPages = Math.ceil(total / limit);
    const paginationContainer = document.getElementById('paginationContainer');

    // Clear existing pagination
    paginationContainer.innerHTML = '';

    // Create the navigation structure
    const nav = document.createElement('nav');
    nav.setAttribute('aria-label', 'Page navigation');
    const ul = document.createElement('ul');
    ul.className = 'pagination';

    if (totalPages > 1) {
        // Generate pagination items
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = 'page-item';
            if (i === page) {
                li.classList.add('active');
            }

            const a = document.createElement('a');
            a.className = 'page-link js-pagination-link';
            a.href = '#';
            a.textContent = i;
            a.setAttribute('data-page', i);
            a.addEventListener('click', function(e) {
                e.preventDefault();
                // Here, call your function to fetch and update the data for the selected page
                console.log(`Page ${i} clicked`);
                // For example: fetchData(i); where fetchData is your function to update the content
                const url = '/api/posts'
                fetchPosts(url, i)
            });

            li.appendChild(a);
            ul.appendChild(li);
        }
    }

    // Append the pagination to the navigation element and then to the container
    nav.appendChild(ul);
    paginationContainer.appendChild(nav);
}

function showSpinner () {
    const spinnerDiv = document.getElementById('spinner-container');
    if (spinnerDiv) {
        spinnerDiv.classList.add('d-flex');
    }
}

function hideSpinner () {
    const spinnerDiv = document.getElementById('spinner-container');
    if (spinnerDiv) {
        spinnerDiv.classList.remove('d-flex');
        spinnerDiv.classList.add('d-none');
    }
}

function fetchPosts(url, page=1, title='') {
    showSpinner();
    // Perform the AJAX request
    url += `?page=${page}`

    if (title) {
        url += `&title=${encodeURIComponent(title)}`;
    }

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(res => {
            const postsContainer = document.getElementById('postsContainer');
            postsContainer.innerHTML = ''; // This line clears the postsContainer

            const {
                posts,
                is_authenticated,
                is_admin,
                total,
                page,
                limit,
                translations,
            } = res;

            updatePagination(total, page, limit);

            posts.forEach(post => {
                // Create the main div for the post
                const postDiv = document.createElement('div');
                postDiv.classList.add('col-12', 'col-sm-6', 'col-xl-4', 'mb-4');

                // Create the title and link
                const titleDiv = document.createElement('div');
                titleDiv.classList.add('h2');
                const titleLink = document.createElement('a');
                titleLink.setAttribute('href', `/post/${post.slug}`);
                titleLink.classList.add('text-decoration-none');
                titleLink.textContent = post.title.length > 20 ? post.title.slice(0, 20) + '...' : post.title;
                titleDiv.appendChild(titleLink);

                if (post.image) {
                    const imgDiv = document.createElement('div');
                    imgDiv.classList.add('post-image-container', 'my-2');

                    const img = document.createElement('img');
                    img.setAttribute('src', `/uploads/post_images/${post.image}`);
                    img.setAttribute('style', 'max-height: 384px');
                    img.classList.add('d-inline-block', 'rounded', 'border', 'border-2', 'border-white', 'w-100');
                    img.setAttribute('alt', 'Post image');

                    imgDiv.appendChild(img);
                    postDiv.appendChild(imgDiv);
                }

                // Create the content div
                const contentDiv = document.createElement('div');
                contentDiv.classList.add('lead', 'text-justify', 'my-2');
                contentDiv.textContent = post.content.length > 400 ? post.content.slice(0, 400) + '...' : post.content;

                // Append the title and content to the main post div
                postDiv.appendChild(titleDiv);
                postDiv.appendChild(contentDiv);

                // More elements like author, like/unlike buttons, etc., would be similarly created and appended here

                const infoDiv = document.createElement('div');
                infoDiv.classList.add('px-3', 'py-2', 'border', 'rounded');

                const authorDiv = document.createElement('div');
                authorDiv.classList.add('my-2');
                const authorText = document.createElement('div');
                authorText.classList.add('small', 'text-secondary');
                authorText.innerHTML = `${translations.author}: <a href="/profile/${post.user.id}" class="text-decoration-none">${post.user.email}</a>`;
                authorDiv.appendChild(authorText);

                const dateDiv = document.createElement('div');
                dateDiv.classList.add('small', 'text-secondary');
                dateDiv.textContent = `${translations.written}: ${post.createdAt}`;

                authorDiv.appendChild(dateDiv);
                infoDiv.appendChild(authorDiv);

                // Append the author and date info to the main post div
                postDiv.appendChild(infoDiv);

                if (is_authenticated) {
                    // Create form
                    const form = document.createElement('form');
                    form.setAttribute('method', 'post');
                    form.classList.add('d-flex', 'align-items-center');

                    // Decide whether to generate a Like or Unlike form based on whether the user has liked the post
                    if (post.isLiked) {
                        form.setAttribute('action', `/unlike/${post.id}`);
                        form.innerHTML = `
                                    <button type="submit" class="btn btn-link text-decoration-none my-1 px-2 py-1 border border-secondary">
                                        ${translations.unlike} <span class="px-2 rounded bg-light">${post.likesCount}</span>
                                        <i class="bi bi-heart-fill text-danger"></i>
                                    </button>
                                `;
                    } else {
                        form.setAttribute('action', `/like/${post.id}`);
                        form.innerHTML = `
                                    <button type="submit" class="btn btn-link text-decoration-none my-1 px-2 py-1 border border-secondary">
                                        ${translations.like} <span class="px-2 rounded bg-light">${post.likesCount}</span>
                                        <i class="bi bi-heart text-danger"></i>
                                    </button>
                                `;
                    }

                    // Append the form to the main post div
                    infoDiv.appendChild(form);

                    // Dynamically generate Add to Favorites / Remove from Favorites section
                    const favoriteForm = document.createElement('form');
                    form.setAttribute('method', 'post');
                    favoriteForm.classList.add('d-flex', 'align-items-center');

                    if (post.isFavorite) {
                        favoriteForm.setAttribute('action', `/remove-favorite/${post.id}`);
                        favoriteForm.innerHTML = `
                                    <button type="submit" class="btn btn-link text-decoration-none my-1 px-2 py-1 border border-secondary">
                                        <span class="mr-1">${translations.remove_from_favorites}</span>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    </button>
                                `;
                    } else {
                        favoriteForm.setAttribute('action', `/favorite/${post.id}`);
                        favoriteForm.innerHTML = `
                                    <button type="submit" class="btn btn-link text-decoration-none my-1 px-2 py-1 border border-secondary">
                                        <span class="mr-1">${translations.add_to_favorites}</span>
                                        <i class="bi bi-star text-warning"></i>
                                    </button>
                                `;
                    }

                    // Append the favoriteForm to the postDiv
                    infoDiv.appendChild(favoriteForm);

                    const actionDiv = document.createElement('div');
                    actionDiv.classList.add('my-1', 'd-flex');

                    // Edit section
                    if (is_admin) {
                        const editDiv = document.createElement('div');
                        const editLink = document.createElement('a');
                        editLink.setAttribute('href', `/post/${post.id}/edit`);
                        editLink.classList.add('mr-2', 'text-decoration-none');
                        const editLinkSpan = document.createElement('span');
                        editLinkSpan.classList.add('mr-1');
                        editLinkSpan.textContent = translations.edit;

                        const editIcon = document.createElement('i');
                        editIcon.classList.add('mr-2', 'bi', 'bi-pencil-square', 'text-primary');
                        editLink.appendChild(editLinkSpan);
                        editLink.appendChild(editIcon);

                        editDiv.appendChild(editLink);
                        // editDiv.appendChild(editIcon);
                        actionDiv.appendChild(editDiv);
                    }

                    // actionDiv.appendChild(commentDiv);

                    // Append the actionDiv to the postDiv
                    infoDiv.appendChild(actionDiv);
                }

                // Comment section
                const commentDiv = document.createElement('div');
                commentDiv.classList.add('my-1')
                const commentLink = document.createElement('a');
                commentDiv.innerHTML = `${translations.comments}: <span class="px-1 rounded bg-light">${post.commentsCount}</span>`;

                commentDiv.appendChild(commentLink);
                infoDiv.appendChild(commentDiv);

                // Append the constructed post div to the posts container
                postsContainer.appendChild(postDiv);
            });
        })
        .catch(error => console.error('Error fetching data:', error))
        .finally(() => {
            hideSpinner();
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const titleFilterInput = document.getElementById('titleFilter');
    const url = '/api/posts';
    fetchPosts(url, 1, titleFilterInput.value);

    titleFilterInput.addEventListener('keyup', function() {
        fetchPosts(url, 1, this.value);
    });

    document.querySelectorAll('.js-pagination-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link action

            let page = this.getAttribute('data-page'); // Get the page number
            // let url = '/posts'; // Construct the URL

            fetchPosts(url, parseInt(page) + 1)
        });
    });
});