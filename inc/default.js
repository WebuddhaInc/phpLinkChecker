
var winScrollTimer;
function doWinScroll(){

  /*
  http://mootools.net/docs/core/Utilities/Selectors
  http://mootools.net/docs/core/Element/Element.Dimensions
    scrollTo
    getSize
    getScrollSize
    getScroll
    getPosition
    setPosition
    getCoordinates
    getOffsetParent
  */

  var doc = $(document.body);
  var dim = doc.getScrollSize();
  var scr = doc.getScroll();
  if( scr.y < dim.y )
    scrollTo( 0, dim.y );
  winScrollTimer = setTimeout('doWinScroll();',1000);

}