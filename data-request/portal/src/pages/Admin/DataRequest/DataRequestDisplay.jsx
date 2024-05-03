import PropTypes from 'prop-types'
import BasicFieldItem from 'components/BasicFieldItem'
import DataRequestConstituentsDisplay from './DataRequestConstituentsDisplay'

export default function DataRequestDisplay({ sentOrReceived, refreshMethod, fileTypes, dataRequest, constituents }) {
	return (
		<>
			<BasicFieldItem label='From' value={dataRequest.fromEntity.name} />
			<BasicFieldItem label='To' value={dataRequest.toEntity.name} />
			<BasicFieldItem label='Description' value={dataRequest.description} />
			<DataRequestConstituentsDisplay sentOrReceived={sentOrReceived} refreshMethod={refreshMethod} fileTypes={fileTypes} constituents={constituents} />
			<BasicFieldItem label='Status' value={dataRequest.status} />
			<BasicFieldItem label='Last Updated' value={dataRequest.updatedAt} />
			<BasicFieldItem label='Created' value={dataRequest.createdAt} />
		</>
	)
}

DataRequestDisplay.propTypes = {
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
	}).isRequired
}
