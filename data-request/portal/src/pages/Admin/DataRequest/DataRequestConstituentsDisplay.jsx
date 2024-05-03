import PropTypes from 'prop-types'
import DataRequestContituentDisplay from './DataRequestConstituentDisplay'

export default function DataRequestConstituentsDisplay({ sentOrReceived, refreshMethod, fileTypes, constituents }) {
	if (typeof constituents.data === 'undefined' || constituents.data.length === 0) {
		return (
			<h3 className='pl-5 text-xs' key='no_data_heading'>No files attached to this request. Please contact requestor.</h3>
		)
	}

	const renderedConstituentsDisplays = []
	let iterationIndex = 0
	for (const data of constituents.data) {
		renderedConstituentsDisplays.push(<DataRequestContituentDisplay iterationIndex={iterationIndex} sentOrReceived={sentOrReceived} refreshMethod={refreshMethod} fileTypes={fileTypes} key={data.id} dataRequestConstituent={data} />)
		iterationIndex += 1
	}

	const finalRender = [<h1 className='text-center pt-5 pb-5 text-xl' key='heading'>Files Requested:</h1>]
	finalRender.push(renderedConstituentsDisplays)
	return finalRender
}

DataRequestConstituentsDisplay.propTypes = {
	sentOrReceived: PropTypes.string.isRequired,
	refreshMethod: PropTypes.func.isRequired,
	fileTypes: PropTypes.shape({
		data: PropTypes.any.isRequired
	}).isRequired,
	constituents: PropTypes.shape({
		data: PropTypes.arrayOf(PropTypes.shape({
			label: PropTypes.string.isRequired,
			fileType: PropTypes.any.isRequired
		}))
	}).isRequired
}
