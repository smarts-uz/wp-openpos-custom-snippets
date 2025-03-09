import React from 'react'
import { Button } from 'reactstrap'

export default class HomePageLink extends React.Component {

  render() {
    if ( global.gApp.shouldHideSpecWithMenuCaption(this.props.menuCaption) )
      return null
    let f = this.props.onClick
    if ( ! f )
      f = this.onClick.bind(this)
    let style = {color: "#84BA65", backgroundColor: "transparent", border: "none", paddingBottom: "0px",
      paddingTop: "0px", verticalAlign: "top", fontSize: "16px", marginTop: "6px"}
    let id = this.props.id
    return (
      <>
        <Button 
          id={id}
          className={"stLink stHomePageLink"}
          style={style}
          onClick={f}
        >
          {global.gApp.nameToIcon(this.props.icon, 'lg')}
          {this.props.menuText.translate()}
        </Button>
        <br/>
      </>
    )
  }

  onClick() {
    global.gApp.invokeMenuByCaption(this.props.menuCaption)
  }

}
