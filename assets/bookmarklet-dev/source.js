(function (location, endpoint, projectId, newWindow) {
    const builder = new URL(endpoint);
    const params = builder.searchParams;

    params.set('href', location.href);
    params.set('title', document.title);
    if (projectId) {
        params.set('projectId', projectId);
    }

    if (newWindow) {
        window.open(builder.href, 'taddoist')
    } else {
        location.assign(builder.toString());
    }

})(location, $__endpoint__, $__projectId__, $__newWindow__);