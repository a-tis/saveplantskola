var config = {
    paths: {
        slick: 'js/slick'
    },
    shim: {
        jquery: {
            exports: '$'
        },
        slick: {
            deps: ['jquery']
        }
    }
};
