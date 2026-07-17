define(['jquery'], function($) {
    const MIN_CHARS = 2, PAGE_SIZE = 20, SEARCH_DELAY = 250;
    function debounce(fn, delay) { let timer=null; return function(){ const ctx=this,args=arguments; clearTimeout(timer); timer=setTimeout(function(){ fn.apply(ctx,args); }, delay); }; }
    function escapeHtml(text){ return $('<div>').text(text || '').html(); }
    function closeAllDropdowns(){ $('.local-exammanager-search-dropdown').hide(); }
    function renderMessage($dropdown, message){ $dropdown.html('<div class="local-exammanager-search-item">'+escapeHtml(message)+'</div>').show(); }
    function appendCourseItems($dropdown, items, state, $text, $hidden, $quiz){
        if(!items || items.length===0){ if(state.page===0) renderMessage($dropdown,'Aucun cours trouvé'); return; }
        if(state.page===0) $dropdown.empty();
        items.forEach(function(item){
            const $item=$('<div>',{'class':'local-exammanager-search-item','html':escapeHtml(item.label)});
            $item.on('click', function(){ $text.val(item.label); $hidden.val(item.id); $dropdown.hide(); loadQuizzes(item.id, $quiz); });
            $dropdown.append($item);
        });
        if(state.hasmore){
            const $more=$('<div>',{'class':'local-exammanager-search-item','text':state.loadingMore ? 'Chargement...' : 'Afficher plus'});
            $more.on('click', function(){ if(state.loadingMore) return; state.loadingMore=true; searchCourses(state,$text,$hidden,$dropdown,$quiz,true); });
            $dropdown.append($more);
        }
        $dropdown.show();
    }
    function searchCourses(state,$text,$hidden,$dropdown,$quiz,append){
        const query=$.trim($text.val());
        if(query.length<MIN_CHARS){ $hidden.val(''); $dropdown.hide(); return; }
        if(!append){ state.page=0; state.hasmore=false; state.loadingMore=false; }
        if(state.controller) state.controller.abort();
        state.controller=new AbortController();
        if(!append) renderMessage($dropdown,'Recherche en cours...');
        const offset=state.page*PAGE_SIZE;
        const url=M.cfg.wwwroot+'/local/exammanager/ajax_search_courses.php?query='+encodeURIComponent(query)+'&limit='+PAGE_SIZE+'&offset='+offset+'&sesskey='+encodeURIComponent(M.cfg.sesskey);
        fetch(url,{signal:state.controller.signal, credentials: 'same-origin'})
            .then(function(response){ if(!response.ok) throw new Error('HTTP'); return response.json(); })
            .then(function(data){ const items=data.results||[]; state.hasmore=!!data.hasmore; appendCourseItems($dropdown,items,state,$text,$hidden,$quiz); if(items.length>0) state.page++; state.loadingMore=false; })
            .catch(function(error){ if(error.name==='AbortError') return; state.loadingMore=false; renderMessage($dropdown,'Erreur pendant la recherche'); });
    }
    function loadQuizzes(courseid,$quiz){
        if(!courseid){ $quiz.html('<option value="">Choisir un quiz</option>'); return; }
        $quiz.html('<option value="">Chargement...</option>');
        fetch(M.cfg.wwwroot+'/local/exammanager/ajax_get_quiz.php?courseid='+encodeURIComponent(courseid)+'&sesskey='+encodeURIComponent(M.cfg.sesskey), {credentials: 'same-origin'})
            .then(function(response){ if(!response.ok) throw new Error('HTTP'); return response.json(); })
            .then(function(data){
                $quiz.html('<option value="">Choisir un quiz</option>');
                if(!data || data.length===0){ $quiz.append('<option value="">Aucun quiz trouvé</option>'); return; }
                data.forEach(function(q){
                    const quizid = parseInt(q.id, 10);
                    const quizname = (q && typeof q.name !== 'undefined') ? String(q.name) : '';
                    if (!Number.isInteger(quizid) || quizid <= 0) {
                        return;
                    }
                    const $option = $('<option>', { value: String(quizid), text: quizname });
                    $quiz.append($option);
                });
            })
            .catch(function(){ $quiz.html('<option value="">Erreur de chargement</option>'); });
    }
    function initRow($wrapper){
        const row=$wrapper.data('row'), $text=$wrapper.find('.course-search-text'), $hidden=$wrapper.find('.course-id-hidden'), $dropdown=$wrapper.find('.local-exammanager-search-dropdown'), $quiz=$('#quiz-'+row);
        const state={page:0,hasmore:false,loadingMore:false,controller:null};
        const debouncedSearch=debounce(function(){ $hidden.val(''); $quiz.html('<option value="">Choisir un quiz</option>'); searchCourses(state,$text,$hidden,$dropdown,$quiz,false); }, SEARCH_DELAY);
        $text.on('input', function(){ const query=$.trim($text.val()); if(query.length<MIN_CHARS){ $hidden.val(''); $dropdown.hide(); $quiz.html('<option value="">Choisir un quiz</option>'); return; } debouncedSearch(); });
        $text.on('focus', function(){ const query=$.trim($text.val()); if(query.length>=MIN_CHARS && $dropdown.children().length>0) $dropdown.show(); });
    }
    return { init: function(){ $(document).on('click', function(e){ if(!$(e.target).closest('.local-exammanager-search-wrapper').length) closeAllDropdowns(); }); $('.local-exammanager-search-wrapper').each(function(){ initRow($(this)); }); } };
});