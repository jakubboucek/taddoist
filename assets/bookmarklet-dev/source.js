(function(location, endpoint, projectId){
    const builder = new URL(endpoint);
    const params = builder.searchParams;

    params.append('href', location.href);
    params.append('title', document.title);
    if(projectId) {
        params.append('projectId', projectId);
    }
    location.href = builder.href;


})(location, $__endpoint__, $__projectId__);