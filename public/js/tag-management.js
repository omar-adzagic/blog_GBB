let availableTags = []; // Store the tags fetched from the API
let selectedTagIds = []; // This will store the tag IDs

function addTag(tag) {
    // Check if the tagName exists in availableTags
    const tagExists = availableTags.some(currentTag => currentTag.name === tag.name);
    console.log('availableTags', availableTags, 'tagName', tag.name, 'tagExists', tagExists)
    if (tagExists) {
        selectedTagIds.push(tag.id);
        const newTag = document.createElement('span');
        newTag.className = 'badge bg-primary p-2 mr-2 text-white';
        newTag.innerHTML = `${tag.name} <i class="bi bi-x-lg ml-1 text-danger" onclick="removeTag(this)"></i>`;
        newTag.setAttribute('data-tag-id', tag.id);
        const tagsContainer = document.getElementById('tags-container');
        tagsContainer.appendChild(newTag);
        updateHiddenInputWithTags();
    } else {
        alert('Please select a tag from the suggestions.');
    }
}

function removeTag(element) {
    console.log('element', element.parentNode)
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
        const tag = postTag.tag;
        availableTags.push(tag);
        addTag(tag);
    });

    const input = document.getElementById('tag-input');
    input.addEventListener('input', fetchTagSuggestions);

    document.getElementById('add-tag-btn').addEventListener('click', function(e) {
        e.preventDefault();
        const tagName = input.value;
        const tag = availableTags.find(tag => tag.name === tagName);
        addTag(tag);
        input.value = '';
    });
});

function fetchTagSuggestions() {
    const input = document.getElementById('tag-input');
    const searchTerm = input.value.trim();

    if (searchTerm) {
        const url = `/admin/tags/search?q=${encodeURIComponent(searchTerm)}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log('data.tags', data.tags)
                availableTags = [...availableTags, ...data.tags];
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
