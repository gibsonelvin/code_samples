import { useManagedDrawer } from 'hooks'
import { Button } from 'kit'
import PropTypes from 'prop-types'
import DataRequestDisplay from './DataRequestDisplay'

export default function DataRequestDetailsDrawer({ sentOrReceived, refreshMethod, fileTypes, dataRequest, constituents, hideDetailView }) {
	const drawer = useManagedDrawer(() => {}, { noPadding: true })

	return (
		<article style={drawer.style} className={drawer.className}>
			<h1 className='text-xl text-center pt-4 leading-none mb-4'>
				Data Request #
				{dataRequest.id}
			</h1>
			<h1 className='text-xl text-center'>View Details Below:</h1>
			<DataRequestDisplay sentOrReceived={sentOrReceived} refreshMethod={refreshMethod} fileTypes={fileTypes} dataRequest={dataRequest} constituents={constituents} />
			<Button className='w-full' variant='outline' size='sm' onClick={hideDetailView}>Close</Button>
		</article>
	)
}

DataRequestDetailsDrawer.propTypes = {
	sentOrReceived: PropTypes.string.isRequired,
	refreshMethod: PropTypes.func.isRequired,
	fileTypes: PropTypes.shape({
		data: PropTypes.any.isRequired
	}).isRequired,
	dataRequest: PropTypes.shape({
		id: PropTypes.number.isRequired,
		description: PropTypes.string.isRequired,
		status: PropTypes.string.isRequired,
		createdAt: PropTypes.string.isRequired,
		updatedAt: PropTypes.string.isRequired,
		fromEntityId: PropTypes.number.isRequired,
		toEntityId: PropTypes.number.isRequired,
		toEntity: PropTypes.object.isRequired,
		fromEntity: PropTypes.object.isRequired
	}).isRequired,
	constituents: PropTypes.shape({
		data: PropTypes.any
	}).isRequired,
	hideDetailView: PropTypes.func.isRequired
}
