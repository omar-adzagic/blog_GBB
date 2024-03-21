let availableTags = []; // Store the tags fetched from the API
let selectedTagIds = []; // This will store the tag IDs

function addTag(postTag) {
    // Check if the tagName exists in availableTags
    const tagExists = availableTags.some(currentTag => currentTag.name === postTag.name);
    if (tagExists) {
        selectedTagIds.push(postTag.id);
        const newTag = document.createElement('span');
        newTag.className = 'badge bg-primary p-2 mr-2 text-white';
        newTag.innerHTML = `${postTag.name} <i class="bi bi-x-lg ml-1 text-danger" onclick="removeTag(this)"></i>`;
        newTag.setAttribute('data-tag-id', postTag.id);
        const tagsContainer = document.getElementById('tags-container');
        tagsContainer.appendChild(newTag);
        updateHiddenInputWithTags();
    } else {
        alert('Please select a tag from the suggestions.');
    }
}

function removeTag(element) {
    const removedTagId = parseInt(element.parentNode.dataset.tagId);
    selectedTagIds = selectedTagIds.filter(tagId => tagId !== removedTagId);
    element.parentNode.remove(); // Remove the tag badge
    updateHiddenInputWithTags();
}

function updateHiddenInputWithTags() {
    document.getElementById('tag-ids').value = selectedTagIds.join(',');
}

document.addEventListener('DOMContentLoaded', function() {
    const tagsContainer = document.getElementById('tags-container');
    console.log('tagsContainer.dataset', tagsContainer.dataset)
    const postTags = JSON.parse(tagsContainer.dataset.postTagsJson);

    postTags.forEach(postTag => {
        availableTags.push(postTag);
        addTag(postTag);
    });

    const input = document.getElementById('tag-input');
    input.addEventListener('input', fetchTagSuggestions);

    document.getElementById('add-tag-btn').addEventListener('click', function(e) {
        e.preventDefault();
        const name = input.value;
        const postTag = availableTags.find(postTag => postTag.name === name);
        addTag(postTag);
        input.value = '';
    });
});

function fetchTagSuggestions() {
    const input = document.getElementById('tag-input');
    const searchTerm = input.value.trim();

    if (searchTerm) {
        const url = `/api/tags/search?q=${encodeURIComponent(searchTerm)}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log('data.tags', data.tags)
                availableTags = [...availableTags, ...data.tags];
                console.log('availableTags', availableTags)
                displayTagSuggestions(data.tags);
            });
    } else {
        clearTagSuggestions();
    }
}

function displayTagSuggestions(tags) {
    const suggestionsContainer = document.getElementById('tag-suggestions');
    suggestionsContainer.innerHTML = ''; // Clear previous suggestions

    tags.forEach(tag => {
        const suggestion = document.createElement('button');
        suggestion.type = 'button';
        suggestion.classList.add('list-group-item', 'list-group-item-action', 'suggestion');
        suggestion.textContent = tag.name;
        suggestion.addEventListener('click', () => {
            selectTag(tag);
            availableTags = []; // Clear available tags since a selection has been made
        });
        suggestionsContainer.appendChild(suggestion);
    });
}

function selectTag(tag) {
    const input = document.getElementById('tag-input');
    input.value = ''; // Clear input value after selection
    if (!selectedTagIds.includes(tag.id)) {
        addTag(tag); // Directly add the selected tag
    }
    clearTagSuggestions(); // Clear the suggestions
}

function clearTagSuggestions() {
    document.getElementById('tag-suggestions').innerHTML = '';
}
