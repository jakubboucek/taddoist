(function (location, endpoint, projectId, newWindow) {
    const builder = new URL(endpoint);
    const params = builder.searchParams;

    params.append('href', location.href);
    params.append('title', document.title);
    if (projectId) {
        params.append('projectId', projectId);
    }

    if (newWindow) {
        window.open(builder.href, 'taddoist')
    } else {
        location.href = builder.href;
    }


})(location, $__endpoint__, $__projectId__, $__newWindow__);