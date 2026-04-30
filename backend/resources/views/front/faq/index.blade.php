@extends('front.layout.main')
@section('content')
<style>
    /* FAQ Scrollable Container */
.faq-scrollable-container {
    max-height: 540px; /* Adjust this value as needed */
    overflow-y: auto;
    padding-right: 15px;
    margin-bottom: 30px;
    
}

/* Custom scrollbar (optional) */
.faq-scrollable-container::-webkit-scrollbar {
    width: 8px;
}

.faq-scrollable-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}
.faq-scrollable-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
        <section class="page-header">
            <div class="page-header__bg"></div>
            <div class="container">
                <h1 class="page-header__title bw-split-in-right">{{ strtoupper($site_title) }}</h1>
                <ul class="karoons-breadcrumb list-unstyled">
                    <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
                    <li><span>{{ $site_title }}</span></li>
                </ul>
            </div>
        </section>

        <section class="faq-page-search">
            <div class="container">
                <div class="faq-page-search__inner wow fadeInUp" data-wow-duration="1500ms">
                    <h3 class="faq-page-search__title">Search Your Questions<br> Queries here</h3>
                    <form id="faqSearchForm" class="faq-page-search__form">
                        <input type="text" id="faqSearchInput" placeholder="Search Here" />
                        <button type="submit" class="faq-page-search__form__btn" aria-label="search submit">
                            <span><i class="fas fa-search"></i></span>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section class="faq-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="faq-page__image">
                            <img src="front/images/resources/faq-page-1.jpg" alt="Car Service">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <!-- Added scrollable container here -->
                        <div class="faq-scrollable-container">
                            <div id="faqAccordion" class="faq-page__accordion karoons-accrodion" data-grp-name="karoons-accrodion">
                                @if($faqs->count())
                                    @foreach($faqs as $key => $faq)
                                        <div class="accrodion @if($key == 0) active @endif" data-search-term="{{ strtolower($faq->name) }}" id="faq-{{ $key }}">
                                            <div class="accrodion-title">
                                                <h4>
                                                    <span class="accrodion-title__icon">

                                                    </span>
                                                    {{ $faq->name }}
                                                </h4>
                                            </div>
                                            <div class="accrodion-content" @if($key != 0)  @endif>
                                                <div class="inner">
                                                    {!! $faq->description !!}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="no-faqs-found">No FAQs found</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
@endsection

@section('javascript')
<script>
    $(function() {
        // FAQ Search Functionality
        $('#faqSearchForm').on('submit', function(e) {
            e.preventDefault();
            performSearch();
        });

        $('#faqSearchInput').on('keyup', function() {
            performSearch();
        });

        function performSearch() {
            var searchTerm = $('#faqSearchInput').val().toLowerCase().trim();
            var hasResults = false;
            var container = $('.faq-scrollable-container');
            var hasScrolled = false;

            if (searchTerm === '') {
                $('#faqAccordion .accrodion').show().removeClass('active');
                $('#faqAccordion .accrodion-content').hide();
                $('#faqAccordion .accrodion').first().addClass('active').find('.accrodion-content').show();
                $('#faqAccordion .no-results').remove();
                container.scrollTop(0);
                return;
            }

            $('#faqAccordion .accrodion').each(function() {
                var faqItem = $(this);
                var faqText = faqItem.data('search-term') + ' ' +
                             faqItem.find('.accrodion-content').text().toLowerCase();

                if (faqText.includes(searchTerm)) {
                    faqItem.show().addClass('active');
                    faqItem.find('.accrodion-content').show();
                    hasResults = true;

                    // Scroll to the first matching result
                    if (!hasScrolled) {
                        var scrollTo = faqItem.offset().top - container.offset().top + container.scrollTop();
                        container.animate({
                            scrollTop: scrollTo - 20
                        }, 300);
                        hasScrolled = true;
                    }
                } else {
                    faqItem.hide().removeClass('active');
                    faqItem.find('.accrodion-content').hide();
                }
            });

            if (!hasResults) {
                $('#faqAccordion').append('<div class="no-results">No matching FAQs found</div>');
                container.scrollTop(0);
            } else {
                $('#faqAccordion .no-results').remove();
            }
        }
    });
</script>

@endsection