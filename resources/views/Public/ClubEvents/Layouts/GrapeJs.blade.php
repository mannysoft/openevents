<script type="text/javascript">
    // https://grapesjs.com/docs/getting-started.html
    const editor = grapesjs.init({
        // Indicate where to init the editor. You can also pass an HTMLElement
        container: '#gjs',
        // container: '.gjs',
        // Get the content for the canvas directly from the element
        // As an alternative we could use: `components: '<h1>Hello World Component!</h1>'`,
        fromElement: true,
        // Size of the editor
        // height: '300px',
        height: '300px',
        width: 'auto',
        // Disable the storage manager for the moment
        // storageManager: false,
        storageManager: {
            type: 'remote',
            autosave: true,
            autoload: true,
            // ...
            // stepsBeforeSave: 10,
            options: {
                remote: {
                    // club-content/* checks the CSRF token: send it as a header, never in the stored body.
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, // Custom headers for the remote storage request
                    urlStore: "{{ route('update.club.content', 'globalmanclub') }}", // Endpoint URL where to store data project
                    urlLoad: "{{ route('get.club.content', 'globalmanclub') }}", // Endpoint URL where to load data project
                }
            },
        },
        assetManager: {

            // Upload endpoint, set `false` to disable upload, default `false`
            upload: "{{ route('update.club.files', 'globalmanclub') }}",

            // The name used in POST to pass uploaded files, default: `'files'`
            uploadName: 'uploadfiles',

            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },

        },
        blockManager: {
            appendTo: '#blocks',
            blocks: [{
                id: 'section', // id is mandatory
                label: '<b>Section</b>', // You can use HTML/SVG inside labels
                attributes: {
                    class: 'gjs-block-section'
                },
                content: `<section>
          <h1>This is a simple title</h1>
          <div>This is just a Lorem text: Lorem ipsum dolor sit amet</div>
        </section>`,
            }, {
                id: 'text',
                label: 'Text',
                content: '<div data-gjs-type="text">Insert your text here</div>',
            }, {
                id: 'image',
                label: 'Image',
                // Select the component once it's dropped
                select: true,
                // You can pass components as a JSON instead of a simple HTML string,
                // in this case we also use a defined component type `image`
                content: {
                    type: 'image'
                },
                // This triggers `active` event on dropped components and the `image`
                // reacts by opening the AssetManager
                activate: true,
            }],
        },
        // Avoid any default panel
        panels: {
            defaults: []
        },
    });
</script>