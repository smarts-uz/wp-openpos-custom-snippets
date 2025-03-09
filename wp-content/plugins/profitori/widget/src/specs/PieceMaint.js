'PieceMaint'.maint()
'Add Order Sales Agent'.title({when: 'adding'})
'Edit Order Sales Agent'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Piece'.datatype({caption: 'Order Sales Agent'})
'order'.field({refersToParent: 'orders', readOnly: true, indexed: true})
'agent'.field({refersTo: 'Agent', allowEmpty: false})

'Piece'.beforeSaving(async function() {
  if ( ! this.order ) return
  let order = await 'orders.RecentOrActive'.bringSingle({id: this.order.id}); if ( ! order ) return null
  let pieces = await 'Piece'.bringChildrenOf(order)
  let thisAgent = await this.toAgent()
  let thisAgentId = thisAgent ? thisAgent.id : null
  for ( var i = 0; i < pieces.length; i++ ) {
    let piece = pieces[i]
    if ( piece.id === this.id ) continue
    let otherAgent = await piece.toAgent()
    let otherAgentId = otherAgent ? otherAgent.id : null
    if ( (this.isNew() || this.propChanged('agent')) && (thisAgentId === otherAgentId) ) {
      throw(new Error("This agent is already an agent for this order"))
    }
  }
})

'Piece'.method('toAgent', async function() {
  return await this.referee('agent')
})
